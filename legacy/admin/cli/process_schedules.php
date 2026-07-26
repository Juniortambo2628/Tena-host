<?php
/**
 * CLI worker to process scheduled exports.
 * Run via cron: php admin/cli/process_schedules.php
 */
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/constants.php';
require_once __DIR__.'/../../vendor/autoload.php';

use Cron\CronExpression;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

$jobsFile = __DIR__.'/../../data/export_schedules.json';
if (! file_exists($jobsFile)) {
    exit("No scheduled jobs\n");
}
$jobs = json_decode(file_get_contents($jobsFile), true) ?: [];
foreach ($jobs as $id => $job) {
    try {
        // Use fromString if available, otherwise fall back to factory for compatibility
        if (method_exists(CronExpression::class, 'fromString')) {
            // use call_user_func to avoid static analysis deprecation warnings
            $expr = call_user_func([CronExpression::class, 'fromString'], $job['cron']);
        } else {
            $expr = CronExpression::factory($job['cron']);
        }
        if (! $expr->isDue()) {
            continue;
        }

        $attempts = $job['attempts'] ?? 0;
        $maxAttempts = 3;

        $exportId = 'sched_'.$id.'_'.time();
        $exportFile = __DIR__."/../exports/{$exportId}.csv";

        // Run users_export_worker directly
        require_once __DIR__.'/../api/users_export_worker.php';
        users_export_worker($exportFile, json_encode([$job['filters'], json_encode($job['columns'])]));

        // Send notification email and write history
        $historyFile = __DIR__.'/../../data/export_history.json';
        $history = file_exists($historyFile) ? (json_decode(file_get_contents($historyFile), true) ?: []) : [];
        $historyEntry = ['job_id' => $id, 'export_id' => $exportId, 'time' => date('c'), 'status' => 'success', 'path' => '/admin/exports/'.basename($exportFile), 'owner' => $job['owner'] ?? null, 'type' => $job['type']];
        $history[] = $historyEntry;
        file_put_contents($historyFile, json_encode($history));

        // attempt to notify owner
        $to = null;
        try {
            $db = (new Database)->getConnection();
            $stmt = $db->prepare('SELECT email FROM users WHERE id = :id');
            $stmt->bindValue(':id', $job['owner']);
            $stmt->execute();
            $u = $stmt->fetch();
            if ($u && filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
                $to = $u['email'];
            }
        } catch (Exception $e) {
            $to = null;
        }

        if ($to) {
            $subject = 'Your scheduled export is ready';
            $message = "Your scheduled export ({$id}) completed. Download: ".BASE_URL.'/admin/exports/'.basename($exportFile);
            // Prefer PHPMailer SMTP if configured
            if (defined('MAILER_SMTP_HOST') && MAILER_SMTP_HOST) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = MAILER_SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = MAILER_SMTP_USER;
                    $mail->Password = MAILER_SMTP_PASS;
                    if (defined('MAILER_SMTP_SECURE') && MAILER_SMTP_SECURE) {
                        $mail->SMTPSecure = MAILER_SMTP_SECURE;
                    }
                    if (defined('MAILER_SMTP_PORT') && MAILER_SMTP_PORT) {
                        $mail->Port = MAILER_SMTP_PORT;
                    }
                    $mail->setFrom(MAILER_FROM, defined('MAILER_FROM_NAME') ? MAILER_FROM_NAME : MAILER_FROM);
                    $mail->addAddress($to);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    $mail->send();
                } catch (MailException $me) {
                    // fallback to mail()
                    @mail($to, $subject, $message, 'From: '.MAILER_FROM);
                }
            } else {
                @mail($to, $subject, $message, 'From: '.MAILER_FROM);
            }
        }

        // Update job metadata
        $jobs[$id]['last_run'] = date('c');
        $jobs[$id]['attempts'] = 0;
        file_put_contents($jobsFile, json_encode($jobs));
        echo "Processed job {$id}\n";
    } catch (Exception $e) {
        // retry logic
        $jobs[$id]['attempts'] = ($jobs[$id]['attempts'] ?? 0) + 1;
        if ($jobs[$id]['attempts'] >= 3) {
            $jobs[$id]['status'] = 'failed';
        }
        file_put_contents($jobsFile, json_encode($jobs));
        echo "Job {$id} failed: {$e->getMessage()}\n";
    }
}

?>


