<?php

namespace App\Support;

use App\Models\Property;

/**
 * The guest splash page: a template, its look, and the guest content a host
 * chooses to show (welcome note, house info, nearby picks, reviews, offers and
 * their other stays). Everything lives in properties.branding_json.
 */
class SplashDesign
{
    public const TEMPLATES = ['bento', 'horizon', 'clover', 'monogram', 'nocturne'];

    /** Templates from the first pass, kept so saved designs keep rendering. */
    public const LEGACY = ['classic' => 'bento', 'retreat' => 'horizon', 'bold' => 'nocturne'];

    public const SECTIONS = ['welcome', 'rules', 'emergency', 'nearby', 'review', 'offers', 'stays'];

    public const CATEGORIES = ['food', 'drink', 'sights', 'shop', 'nature', 'transport', 'health', 'other'];

    public const DEFAULT_ACCENT = '#FFD300';

    /**
     * Validation rules for branding_json, shared by the property form and the splash studio.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $templates = implode(',', array_merge(self::TEMPLATES, array_keys(self::LEGACY)));

        return [
            'branding_json' => 'nullable|array:template,accent,heading,message,button,welcome_note,host_name,review_prompt,sections,house_rules,emergency,nearby,offers,stays',
            'branding_json.template' => 'required_with:branding_json|in:'.$templates,
            'branding_json.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'branding_json.heading' => 'nullable|string|max:100',
            'branding_json.message' => 'nullable|string|max:240',
            'branding_json.button' => 'nullable|string|max:40',
            'branding_json.welcome_note' => 'nullable|string|max:1200',
            'branding_json.host_name' => 'nullable|string|max:80',
            'branding_json.review_prompt' => 'nullable|string|max:160',
            'branding_json.sections' => 'nullable|array:'.implode(',', self::SECTIONS),
            'branding_json.sections.*' => 'boolean',
            'branding_json.house_rules' => 'nullable|array|max:12',
            'branding_json.house_rules.*' => 'nullable|string|max:160',
            'branding_json.emergency' => 'nullable|array|max:8',
            'branding_json.emergency.*.label' => 'nullable|string|max:60',
            'branding_json.emergency.*.phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'],
            'branding_json.nearby' => 'nullable|array|max:12',
            'branding_json.nearby.*.name' => 'nullable|string|max:80',
            'branding_json.nearby.*.category' => 'nullable|in:'.implode(',', self::CATEGORIES),
            'branding_json.nearby.*.note' => 'nullable|string|max:200',
            'branding_json.nearby.*.distance' => 'nullable|string|max:30',
            'branding_json.nearby.*.url' => 'nullable|url:http,https|max:500',
            'branding_json.offers' => 'nullable|array|max:8',
            'branding_json.offers.*.title' => 'nullable|string|max:80',
            'branding_json.offers.*.detail' => 'nullable|string|max:200',
            'branding_json.offers.*.when' => 'nullable|string|max:60',
            'branding_json.stays' => 'nullable|array|max:8',
            'branding_json.stays.*.property_id' => 'nullable|integer',
            'branding_json.stays.*.title' => 'nullable|string|max:80',
            'branding_json.stays.*.detail' => 'nullable|string|max:200',
            'branding_json.stays.*.price' => 'nullable|string|max:40',
            'branding_json.stays.*.url' => 'nullable|url:http,https|max:500',
        ];
    }

    /**
     * Drop empty list rows and coerce the section switches so the saved JSON stays tidy.
     *
     * @param  array<string, mixed>  $branding
     * @return array<string, mixed>
     */
    public static function clean(array $branding): array
    {
        $rows = fn (string $key, string $required) => array_values(array_filter(
            array_map(fn ($row) => is_array($row) ? array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row) : [], $branding[$key] ?? []),
            fn ($row) => ! empty($row[$required]) || ($key === 'stays' && ! empty($row['property_id'])),
        ));

        $branding['template'] = self::LEGACY[$branding['template'] ?? ''] ?? ($branding['template'] ?? 'bento');
        $branding['sections'] = array_map(fn ($on) => filter_var($on, FILTER_VALIDATE_BOOLEAN), $branding['sections'] ?? []);
        $branding['house_rules'] = array_values(array_filter(array_map(fn ($r) => trim((string) $r), $branding['house_rules'] ?? [])));
        $branding['emergency'] = $rows('emergency', 'phone');
        $branding['nearby'] = $rows('nearby', 'name');
        $branding['offers'] = $rows('offers', 'title');
        $branding['stays'] = $rows('stays', 'title');
        $branding['stays'] = array_map(fn ($row) => ['property_id' => empty($row['property_id']) ? null : (int) $row['property_id']] + $row, $branding['stays']);

        return $branding;
    }

    /** The property's brand colour, or the default when unset or malformed. */
    public static function accent(?Property $property): string
    {
        $accent = $property?->branding_json['accent'] ?? null;

        return is_string($accent) && preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : self::DEFAULT_ACCENT;
    }

    public static function forProperty(?Property $property, ?string $template = null): array
    {
        $settings = $property?->branding_json ?? [];
        $template ??= $settings['template'] ?? 'bento';
        $template = self::LEGACY[$template] ?? $template;
        $accent = self::accent($property);

        $on = fn (string $section) => (bool) ($settings['sections'][$section] ?? true);
        $list = fn (string $key) => array_values(array_filter((array) ($settings[$key] ?? []), 'is_array'));

        $nearby = array_map(fn ($place) => [
            'name' => (string) ($place['name'] ?? ''),
            'category' => in_array($place['category'] ?? null, self::CATEGORIES, true) ? $place['category'] : 'other',
            'note' => $place['note'] ?? null,
            'distance' => $place['distance'] ?? null,
            'url' => ($place['url'] ?? null) ?: 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(trim(($place['name'] ?? '').' '.($property?->address ?? ''))),
        ], array_filter($list('nearby'), fn ($p) => ! empty($p['name'])));

        $content = [
            'welcome' => $on('welcome') && ! empty($settings['welcome_note']) ? [
                'note' => $settings['welcome_note'],
                'host' => $settings['host_name'] ?? null,
            ] : null,
            'rules' => $on('rules') ? array_values(array_filter((array) ($settings['house_rules'] ?? []), fn ($r) => is_string($r) && trim($r) !== '')) : [],
            'emergency' => $on('emergency') ? array_map(fn ($c) => [
                'label' => $c['label'] ?? 'Call',
                'phone' => $c['phone'],
                'tel' => preg_replace('/[^0-9+]/', '', $c['phone']),
            ], array_filter($list('emergency'), fn ($c) => ! empty($c['phone']))) : [],
            'nearby' => $on('nearby') ? array_values($nearby) : [],
            'review' => $on('review') && $property?->review_url ? [
                'url' => $property->review_url,
                'prompt' => ($settings['review_prompt'] ?? null) ?: 'Loved your stay? A Google review helps other guests find us.',
            ] : null,
            'offers' => $on('offers') ? array_values(array_filter($list('offers'), fn ($o) => ! empty($o['title']))) : [],
            'stays' => $on('stays') ? self::stays($property, $list('stays')) : [],
        ];

        return [
            'template' => in_array($template, self::TEMPLATES, true) ? $template : 'bento',
            'heading' => $settings['heading'] ?? null,
            'message' => $settings['message'] ?? 'Connect to the WiFi and stay in touch.',
            'button' => $settings['button'] ?? 'Connect to WiFi',
            'accent' => $accent,
            'ink' => self::readableInk($accent),
            'content' => $content,
            'hasContent' => (bool) array_filter($content),
        ];
    }

    /**
     * Cross-sell cards. A card linked to another of the host's properties borrows its
     * name and photo; ids that belong to someone else are ignored.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected static function stays(?Property $property, array $items): array
    {
        if (! $property) {
            return [];
        }

        $ids = array_filter(array_map(fn ($i) => (int) ($i['property_id'] ?? 0), $items));
        $own = $ids ? Property::where('user_id', $property->user_id)->whereKey($ids)->get()->keyBy('id') : collect();

        return array_values(array_filter(array_map(function ($item) use ($own) {
            $linked = $own->get((int) ($item['property_id'] ?? 0));
            $title = ($item['title'] ?? null) ?: $linked?->name;

            return $title ? [
                'title' => $title,
                'detail' => ($item['detail'] ?? null) ?: $linked?->address,
                'price' => $item['price'] ?? null,
                'url' => $item['url'] ?? null,
                'image' => self::imageUrl($linked?->splash_image_path),
            ] : null;
        }, $items)));
    }

    /** Black or white text, whichever reads better on the accent colour. */
    public static function readableInk(string $hex): string
    {
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
        $lin = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $luminance > 0.179 ? '#111111' : '#FFFFFF';
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
