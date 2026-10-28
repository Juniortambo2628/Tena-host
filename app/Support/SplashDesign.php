<?php

namespace App\Support;

use App\Models\Property;

class SplashDesign
{
    public const TEMPLATES = ['classic', 'retreat', 'bold'];

    public static function forProperty(?Property $property, ?string $template = null): array
    {
        $settings = $property?->branding_json ?? [];
        $template ??= $settings['template'] ?? 'classic';
        $accent = $settings['accent'] ?? '#FFD300';
        if (! is_string($accent) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
            $accent = '#FFD300';
        }

        return [
            'template' => in_array($template, self::TEMPLATES, true) ? $template : 'classic',
            'heading' => $settings['heading'] ?? null,
            'message' => $settings['message'] ?? 'Connect to the WiFi and stay in touch.',
            'button' => $settings['button'] ?? 'Connect to WiFi',
            'accent' => $accent,
        ];
    }

    public static function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Upload helpers historically saved either /storage/path or just path.
        $path = parse_url($path, PHP_URL_PATH);

        return asset(str_starts_with($path, '/storage/') ? $path : 'storage/'.ltrim($path, '/'));
    }
}
