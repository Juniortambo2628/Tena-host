<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasImageUpload
{
    protected function storeImage(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $path = $file->store($directory, $disk);

        return '/storage/'.$path;
    }

    protected function updateImage(?UploadedFile $file, ?string $currentPath, string $directory, string $disk = 'public'): ?string
    {
        if (! $file) {
            return $currentPath;
        }

        if ($currentPath) {
            $oldPath = str_replace('/storage/', '', $currentPath);
            Storage::disk($disk)->delete($oldPath);
        }

        return $this->storeImage($file, $directory, $disk);
    }

    protected function deleteImage(?string $path, string $disk = 'public'): void
    {
        if (! $path) {
            return;
        }

        $relativePath = str_replace('/storage/', '', $path);
        Storage::disk($disk)->delete($relativePath);
    }
}
