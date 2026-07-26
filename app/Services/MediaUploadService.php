<?php

namespace App\Services;

use App\Models\LandingMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class MediaUploadService
{
    private const MAX_IMAGE_SIZE = 20 * 1024 * 1024; // 20MB
    private const MAX_VIDEO_SIZE = 20 * 1024 * 1024; // 20MB
    private const IMAGE_WIDTH = 1200;
    private const THUMBNAIL_WIDTH = 400;
    private const JPEG_QUALITY = 85;
    private const VIDEO_MAX_WIDTH = 1920;
    private const VIDEO_MAX_HEIGHT = 1080;

    /**
     * Allowed MIME types.
     */
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    ];

    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo',
    ];

    /**
     * Upload and process media file.
     */
    public function upload(UploadedFile $file, string $directory = 'landing'): LandingMedia
    {
        $mimeType = $file->getMimeType();

        if (in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            return $this->uploadImage($file, $directory);
        }

        if (in_array($mimeType, self::ALLOWED_VIDEO_TYPES)) {
            return $this->uploadVideo($file, $directory);
        }

        throw new \InvalidArgumentException("Unsupported file type: {$mimeType}");
    }

    /**
     * Upload and optimize image.
     */
    private function uploadImage(UploadedFile $file, string $directory): LandingMedia
    {
        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            throw new \InvalidArgumentException('Image exceeds 20MB limit.');
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = time();

        // Store original
        $originalPath = $file->storeAs(
            "{$directory}/originals",
            "{$originalName}_{$timestamp}." . $file->getClientOriginalExtension(),
            'public'
        );

        // Create optimized version
        $manager = Image::make($file);
        $width = $manager->width();
        $height = $manager->height();

        // Resize if wider than target
        if ($width > self::IMAGE_WIDTH) {
            $manager->resize(self::IMAGE_WIDTH, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $optimizedName = "{$originalName}_{$timestamp}_opt.webp";
        $optimizedPath = "{$directory}/optimized/{$optimizedName}";

        Storage::disk('public')->makeDirectory("{$directory}/optimized");
        $manager->encodeWebP(self::JPEG_QUALITY)->save(
            Storage::disk('public')->path($optimizedPath)
        );

        // Create thumbnail
        $thumbManager = Image::make($file);
        $thumbManager->resize(self::THUMBNAIL_WIDTH, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $thumbName = "{$originalName}_{$timestamp}_thumb.webp";
        $thumbPath = "{$directory}/thumbnails/{$thumbName}";

        Storage::disk('public')->makeDirectory("{$directory}/thumbnails");
        $thumbManager->encodeWebP(self::JPEG_QUALITY)->save(
            Storage::disk('public')->path($thumbPath)
        );

        return LandingMedia::create([
            'original_path' => '/' . Storage::disk('public')->url($originalPath),
            'optimized_path' => $optimizedPath,
            'thumbnail_path' => $thumbPath,
            'mime_type' => 'image/webp',
            'file_size' => Storage::disk('public')->size($optimizedPath),
            'width' => $manager->width(),
            'height' => $manager->height(),
        ]);
    }

    /**
     * Upload and compress video.
     */
    private function uploadVideo(UploadedFile $file, string $directory): LandingMedia
    {
        if ($file->getSize() > self::MAX_VIDEO_SIZE) {
            throw new \InvalidArgumentException('Video exceeds 20MB limit.');
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = time();

        // Store original
        $originalPath = $file->storeAs(
            "{$directory}/videos",
            "{$originalName}_{$timestamp}." . $file->getClientOriginalExtension(),
            'public'
        );

        // Get video dimensions using ffmpeg if available
        $width = null;
        $height = null;
        $duration = null;

        $ffprobePath = $this->findFfmpegBinary('ffprobe');
        if ($ffprobePath) {
            $cmd = sprintf(
                '%s -v error -select_streams v:0 -show_entries stream=width,height,duration -of csv=p=0 "%s"',
                $ffprobePath,
                Storage::disk('public')->path($originalPath)
            );
            $output = @shell_exec($cmd);
            if ($output) {
                $parts = array_map('trim', explode(',', trim($output)));
                if (count($parts) >= 2) {
                    $width = (int) $parts[0];
                    $height = (int) $parts[1];
                    $duration = isset($parts[2]) ? (int) floatval($parts[2]) : null;
                }
            }

            // Compress video with ffmpeg if available
            $ffmpegPath = $this->findFfmpegBinary('ffmpeg');
            if ($ffmpegPath) {
                $compressedName = "{$originalName}_{$timestamp}_compressed.mp4";
                $compressedPath = "{$directory}/videos/compressed/{$compressedName}";

                Storage::disk('public')->makeDirectory("{$directory}/videos/compressed");

                $cmd = sprintf(
                    '%s -i "%s" -vf "scale=min(%d,iw):min(%d,ih)" -c:v libx264 -crf 28 -preset fast -c:a aac -b:a 128k -y "%s"',
                    $ffmpegPath,
                    Storage::disk('public')->path($originalPath),
                    self::VIDEO_MAX_WIDTH,
                    self::VIDEO_MAX_HEIGHT,
                    Storage::disk('public')->path($compressedPath)
                );
                @shell_exec($cmd);

                if (Storage::disk('public')->exists($compressedPath)) {
                    $originalPath = $compressedPath;
                }
            }
        }

        return LandingMedia::create([
            'original_path' => '/' . Storage::disk('public')->url($originalPath),
            'optimized_path' => null,
            'thumbnail_path' => null,
            'mime_type' => $file->getMimeType(),
            'file_size' => Storage::disk('public')->size($originalPath),
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
        ]);
    }

    /**
     * Delete media files.
     */
    public function delete(LandingMedia $media): void
    {
        $paths = array_filter([
            $media->original_path,
            $media->optimized_path,
            $media->thumbnail_path,
        ]);

        foreach ($paths as $path) {
            $relativePath = ltrim($path, '/');
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        $media->delete();
    }

    /**
     * Find ffmpeg/ffprobe binary path.
     */
    private function findFfmpegBinary(string $binary): ?string
    {
        $paths = [
            "/usr/bin/{$binary}",
            "/usr/local/bin/{$binary}",
            "C:\\ffmpeg\\{$binary}.exe",
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try system PATH
        $output = @shell_exec("where {$binary} 2>nul") ?: @shell_exec("which {$binary} 2>/dev/null");
        if ($output) {
            return trim(explode("\n", $output)[0]);
        }

        return null;
    }

    /**
     * Get allowed MIME types.
     */
    public static function getAllowedTypes(): array
    {
        return array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_VIDEO_TYPES);
    }

    /**
     * Get max file size.
     */
    public static function getMaxFileSize(): int
    {
        return max(self::MAX_IMAGE_SIZE, self::MAX_VIDEO_SIZE);
    }
}
