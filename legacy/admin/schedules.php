<?php
require_once '../auth/check_auth.php';
require_once '../config/constants.php';
require_once '../config/database.php';
requireAdmin();

$jobsFile = __DIR__.'/../data/export_schedules.json';
$jobs = file_exists($jobsFile) ? (json_decode(file_get_contents($jobsFile), true) ?: []) : [];

include '../includes/header.php';
?>

<div class="container-fluid px-4">
  <h2>Scheduled Exports</h2>
  <div class="data-table mt-3">
    <div class="p-3">
      <table class="table table-sm">
        <thead><tr><th>ID</th><th>Type</th><th>Cron</th><th>Owner</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $id => $job) { ?>
          <tr>
            <td><?php echo htmlspecialchars($id); ?></td>
            <td><?php echo htmlspecialchars($job['type']); ?></td>
            <td><?php echo htmlspecialchars($job['cron']); ?></td>
            <td><?php echo htmlspecialchars($job['owner']); ?></td>
            <td><?php echo htmlspecialchars($job['created']); ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick="runJob('<?php echo $id; ?>')">Run</button>
              <button class="btn btn-sm btn-outline-danger" onclick="cancelJob('<?php echo $id; ?>')">Cancel</button>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function runJob(id){ fetch('api/run_schedule.php?id='+encodeURIComponent(id)).then(r=>r.json()).then(res=>{ alert(res.message || 'Triggered'); location.reload(); }); }
function cancelJob(id){ if (!confirm('Cancel job '+id+'?')) return; fetch('api/cancel_schedule.php?id='+encodeURIComponent(id),{method:'POST'}).then(r=>r.json()).then(res=>{ alert(res.message || 'Cancelled'); location.reload(); }); }
</script>


