<?php
// Creates tena_staging_package.zip in the current directory
$zipPath = __DIR__.DIRECTORY_SEPARATOR.'tena_staging_package.zip';
@unlink($zipPath);
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    echo "Failed to create zip\n";
    exit(1);
}

$exclude = ['.git', 'node_modules', 'tests', '.env', '.gitignore'];

// directories that should be writable after extraction
$writableDirs = [
    'data',
    'admin/exports',
    'logs',
];

function addFolderToZip($folder, $zip, $basePath, $exclude, $writableDirs)
{
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $item) {
        $filePath = $item->getPathname();
        $relative = ltrim(str_replace($basePath, '', $filePath), DIRECTORY_SEPARATOR);
        // exclude top-level patterns
        foreach ($exclude as $ex) {
            if (strpos($relative, $ex.DIRECTORY_SEPARATOR) === 0 || strpos($relative, $ex) === 0 || $relative === $ex) {
                continue 2;
            }
        }

        if ($item->isDir()) {
            // add empty dir
            $zip->addEmptyDir($relative);
            // set dir mode: writable dirs 0775 else 0755
            $mode = 0755;
            foreach ($writableDirs as $d) {
                if (strpos($relative, $d) === 0 || $relative === $d) {
                    $mode = 0775;
                    break;
                }
            }
            // set unix permissions in zip external attributes
            if (method_exists($zip, 'setExternalAttributesName')) {
                $zip->setExternalAttributesName($relative, ZipArchive::OPSYS_UNIX, ($mode & 0xFFFF) << 16);
            }
        } else {
            $zip->addFile($filePath, $relative);
            // set file mode 0644
            $mode = 0644;
            if (method_exists($zip, 'setExternalAttributesName')) {
                $zip->setExternalAttributesName($relative, ZipArchive::OPSYS_UNIX, ($mode & 0xFFFF) << 16);
            }
        }
    }
}

$base = __DIR__.DIRECTORY_SEPARATOR;
addFolderToZip(__DIR__, $zip, $base, $exclude, $writableDirs);
$zip->close();
echo "Created zip: tena_staging_package.zip\n";
?>


