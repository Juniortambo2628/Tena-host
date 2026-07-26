<?php

/**
 * Simple Package Installer
 * Downloads and installs required packages without Composer
 */
echo "Installing required packages...\n";

// Create vendor directory
if (! is_dir('vendor')) {
    mkdir('vendor', 0755, true);
}

// Download PHPMailer
echo "Downloading PHPMailer...\n";
$phpmailerUrl = 'https://github.com/PHPMailer/PHPMailer/archive/v6.8.0.zip';
$phpmailerZip = 'vendor/phpmailer.zip';

if (downloadFile($phpmailerUrl, $phpmailerZip)) {
    extractZip($phpmailerZip, 'vendor/PHPMailer');
    echo "PHPMailer installed successfully.\n";
} else {
    echo "Failed to download PHPMailer.\n";
}

// Download PhpSpreadsheet
echo "Downloading PhpSpreadsheet...\n";
$spreadsheetUrl = 'https://github.com/PHPOffice/PhpSpreadsheet/archive/1.29.0.zip';
$spreadsheetZip = 'vendor/spreadsheet.zip';

if (downloadFile($spreadsheetUrl, $spreadsheetZip)) {
    extractZip($spreadsheetZip, 'vendor/PhpSpreadsheet');
    echo "PhpSpreadsheet installed successfully.\n";
} else {
    echo "Failed to download PhpSpreadsheet.\n";
}

// Download DomPDF
echo "Downloading DomPDF...\n";
$dompdfUrl = 'https://github.com/dompdf/dompdf/archive/v2.0.3.zip';
$dompdfZip = 'vendor/dompdf.zip';

if (downloadFile($dompdfUrl, $dompdfZip)) {
    extractZip($dompdfZip, 'vendor/dompdf');
    echo "DomPDF installed successfully.\n";
} else {
    echo "Failed to download DomPDF.\n";
}

// Create autoloader
createAutoloader();

echo "Installation completed!\n";

function downloadFile($url, $destination)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $data !== false) {
        return file_put_contents($destination, $data) !== false;
    }

    return false;
}

function extractZip($zipFile, $extractTo)
{
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === true) {
        $zip->extractTo($extractTo);
        $zip->close();

        return true;
    }

    return false;
}

function createAutoloader()
{
    $autoloader = '<?php
// Simple autoloader for Tena system
spl_autoload_register(function ($class) {
    // PHPMailer classes
    if (strpos($class, "PHPMailer") === 0) {
        $file = __DIR__ . "/PHPMailer/src/" . str_replace("\\", "/", $class) . ".php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // PhpSpreadsheet classes
    if (strpos($class, "PhpOffice\\PhpSpreadsheet") === 0) {
        $file = __DIR__ . "/PhpSpreadsheet/src/" . str_replace("\\", "/", $class) . ".php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // DomPDF classes
    if (strpos($class, "Dompdf") === 0) {
        $file = __DIR__ . "/dompdf/src/" . str_replace("\\", "/", $class) . ".php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
';

    file_put_contents('vendor/autoload.php', $autoloader);
    echo "Autoloader created.\n";
}
