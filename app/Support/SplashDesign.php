<?php

namespace App\Support;

use App\Models\Property;

/**
 * The guest splash page: a template, its look, and the guest content a host
 * chooses to show (welcome note, house info, nearby picks, reviews, offers and
 * their other stays). Everything lives in properties.branding_json.
 *
 * Templates, their groups and the business types that recommend them come
 * from resources/data/splash-templates.json, shared with the React editor.
 */
class SplashDesign
{
    public const TEMPLATES = ['bento', 'horizon', 'clover', 'monogram', 'brew', 'bistro', 'nocturne', 'bloom', 'fade', 'pulse', 'calm', 'corner'];

    /**
     * The first five templates. Designs saved before per-template defaults keep
     * their old fallbacks (headline, sign-up fields, opt-in line) so they render as before.
     */
    public const ORIGINAL = ['bento', 'horizon', 'clover', 'monogram', 'nocturne'];

    /** Content sections only short-term rentals need; business pages start with them off. */
    public const RENTAL_SECTIONS = ['rules', 'emergency', 'nearby', 'stays'];

    public const TILE_ICONS = ['guide', 'clock', 'home', 'star', 'menu', 'wifi', 'gift', 'cal', 'list', 'tag', 'cart'];

    /** Clinics ask for a first name and phone number only. */
    public const NAME_AND_PHONE_ONLY = ['calm'];

    private static ?array $catalog = null;

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
            'branding_json' => 'nullable|array:template,accent,heading,message,button,welcome_note,host_name,review_prompt,sections,house_rules,emergency,nearby,offers,stays,fields,opt_in,tiles',
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
            'branding_json.fields' => 'nullable|array:email,birthday',
            'branding_json.fields.*' => 'boolean',
            'branding_json.opt_in' => 'nullable|string|max:120',
            'branding_json.tiles' => 'nullable|array|max:3',
            'branding_json.tiles.*.icon' => 'nullable|in:'.implode(',', self::TILE_ICONS),
            'branding_json.tiles.*.label' => 'nullable|string|max:30',
            'branding_json.tiles.*.url' => 'nullable|url:http,https|max:500',
        ];
    }

    /** groups, businessTypes, templates and accents from the shared JSON. */
    public static function catalog(): array
    {
        return self::$catalog ??= json_decode(file_get_contents(resource_path('data/splash-templates.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed>|null */
    public static function template(string $key): ?array
    {
        return collect(self::catalog()['templates'])->firstWhere('id', self::LEGACY[$key] ?? $key);
    }

    /** @return list<string> */
    public static function businessTypes(): array
    {
        return array_column(self::catalog()['businessTypes'], 'id');
    }

    /** The business type's group (stays, food, beauty, health, retail), or null. */
    public static function groupFor(?string $businessType): ?string
    {
        return collect(self::catalog()['businessTypes'])->firstWhere('id', $businessType)['group'] ?? null;
    }

    public static function recommendedTemplate(?string $businessType): ?string
    {
        return collect(self::catalog()['businessTypes'])->firstWhere('id', $businessType)['template'] ?? null;
    }

    /**
     * A local business rather than a rental: its business type says so, or,
     * when none is set, its owner's account does.
     */
    public static function isBusiness(?Property $property): bool
    {
        $group = self::groupFor($property?->business_type);

        return $group ? $group !== 'stays' : (bool) $property?->host?->isBusiness();
    }

    /**
     * A template's starting content: headline, sign-up fields, opt-in line and,
     * for businesses, the quick tiles. Rental-only sections start off for businesses.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $template, bool $business): array
    {
        $defaults = self::template($template)['defaults'] ?? null;
        if (! $defaults) {
            return [];
        }

        return array_filter([
            'heading' => $defaults['headline'],
            'opt_in' => $defaults['optIn'],
            'fields' => $defaults['fields'],
            'tiles' => $business ? array_map(fn ($t) => $t + ['url' => null], $defaults['tiles']) : null,
            'sections' => $business ? array_fill_keys(self::RENTAL_SECTIONS, false) : null,
        ], fn ($v) => $v !== null);
    }

    /**
     * Fill in the template's starting content wherever the design has none yet.
     *
     * @param  array<string, mixed>  $branding
     * @return array<string, mixed>
     */
    public static function withDefaults(array $branding, bool $business): array
    {
        foreach (self::defaults($branding['template'] ?? 'bento', $business) as $key => $value) {
            if ($key === 'sections') {
                $branding['sections'] = ($branding['sections'] ?? []) + $value;
            } elseif (blank($branding[$key] ?? null)) {
                $branding[$key] = $value;
            }
        }

        return $branding;
    }

    /**
     * Switching template swaps in the new template's starting content, except
     * where the owner changed it from the old template's.
     *
     * @param  array<string, mixed>  $saved
     * @param  array<string, mixed>  $branding
     * @return array<string, mixed>
     */
    public static function switchTemplate(array $saved, array $branding, bool $business): array
    {
        $from = self::LEGACY[$saved['template'] ?? ''] ?? ($saved['template'] ?? null);
        $to = $branding['template'] ?? null;
        if (! $from || ! $to || $from === $to) {
            return $branding;
        }

        $old = self::defaults($from, $business);
        foreach (self::defaults($to, $business) as $key => $value) {
            if ($key === 'sections') {
                continue;
            }
            $current = $branding[$key] ?? null;
            // Tiles compare without the owner's links, which carry over.
            $comparable = $key === 'tiles' && is_array($current) ? array_map(fn ($t) => array_diff_key((array) $t, ['url' => 1]), $current) : $current;
            $oldComparable = $key === 'tiles' ? array_map(fn ($t) => array_diff_key($t, ['url' => 1]), $old[$key] ?? []) : ($old[$key] ?? null);
            if (blank($current) || $comparable == $oldComparable) {
                $branding[$key] = $value;
            }
        }

        return $branding;
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
        if (isset($branding['tiles'])) {
            $branding['tiles'] = array_slice($rows('tiles', 'label'), 0, 3);
        }
        if (isset($branding['fields'])) {
            $branding['fields'] = array_map(fn ($on) => filter_var($on, FILTER_VALIDATE_BOOLEAN), $branding['fields']);
        }
        if (in_array($branding['template'], self::NAME_AND_PHONE_ONLY, true)) {
            $branding['fields'] = ['email' => false, 'birthday' => false];
        }

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
        $template = in_array($template, self::TEMPLATES, true) ? $template : 'bento';
        $accent = self::accent($property);
        $business = self::isBusiness($property);
        $original = in_array($template, self::ORIGINAL, true);
        // Saved designs from before template defaults keep their old fallbacks.
        $defaults = $original ? [] : self::defaults($template, $business);

        $on = fn (string $section) => (bool) ($settings['sections'][$section] ?? $defaults['sections'][$section] ?? true);
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

        $fields = in_array($template, self::NAME_AND_PHONE_ONLY, true)
            ? ['email' => false, 'birthday' => false]
            : array_map('boolval', ($settings['fields'] ?? []) + ($defaults['fields'] ?? []) + ['email' => true, 'birthday' => $business]);

        return [
            'template' => $template,
            'look' => self::template($template)['look'] ?? [],
            'heading' => ($settings['heading'] ?? null) ?: (isset($defaults['heading']) ? str_replace('{name}', (string) $property?->name, $defaults['heading']) : null),
            'message' => $settings['message'] ?? 'Connect to the WiFi and stay in touch.',
            'button' => $settings['button'] ?? 'Connect to WiFi',
            'accent' => $accent,
            'ink' => self::readableInk($accent),
            'content' => $content,
            'hasContent' => (bool) array_filter($content),
            'fields' => $fields,
            'optIn' => ($settings['opt_in'] ?? null) ?: ($defaults['opt_in'] ?? 'Send me offers and early access to direct deals'),
            'tiles' => self::tiles($property, $settings['tiles'] ?? $defaults['tiles'] ?? [], $content, $business),
        ];
    }

    /**
     * The three quick tiles under a business's headline. A tile without its own
     * link opens what its icon suggests: the menu or services on the customer
     * homepage, or the Offers tab.
     *
     * @param  array<int, mixed>  $tiles
     * @param  array<string, mixed>  $content
     * @return list<array{icon: string, label: string, url: ?string, goto: ?string}>
     */
    protected static function tiles(?Property $property, array $tiles, array $content, bool $business): array
    {
        if (! $business) {
            return [];
        }

        return array_values(array_filter(array_map(function ($tile) use ($property, $content) {
            if (! is_array($tile) || blank($tile['label'] ?? null)) {
                return null;
            }
            $icon = in_array($tile['icon'] ?? null, self::TILE_ICONS, true) ? $tile['icon'] : 'star';
            $url = ($tile['url'] ?? null) ?: null;
            $goto = null;
            if (! $url && in_array($icon, ['gift', 'tag'], true) && $content['offers']) {
                $goto = 'offers';
            } elseif (! $url && in_array($icon, ['menu', 'list'], true) && $property?->exists) {
                $url = route('venue.show', $property);
            }

            return ['icon' => $icon, 'label' => (string) $tile['label'], 'url' => $url, 'goto' => $goto];
        }, array_slice($tiles, 0, 3))));
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
