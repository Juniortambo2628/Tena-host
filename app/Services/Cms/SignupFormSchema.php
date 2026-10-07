<?php

namespace App\Services\Cms;

use App\Models\LandingSection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * The sign-up questions live in the CMS (the "signup" section of /hosts and
 * /business, steps.{i}.fields), following Glen's SIGNUP-FIELDS.md. This
 * turns that definition into server-side validation so the form, the stored
 * record and the rules never drift.
 */
class SignupFormSchema
{
    /** Fields every sign-up must carry, whatever the CMS says. */
    private const CORE_RULES = [
        'firstName' => ['required', 'string', 'max:50'],
        'phone' => ['required', 'string', 'regex:/^\+\d{10,15}$/'],
    ];

    /** Envelope keys the form sends alongside the answers. */
    private const META_RULES = [
        'estimatedPriceKES' => ['nullable', 'integer', 'min:0'],
        'consentText' => ['nullable', 'string', 'max:1000'],
        'submittedAt' => ['nullable', 'date'],
        'source' => ['nullable', 'string', 'max:100'],
    ];

    public const META_KEYS = ['consent', 'consentText', 'submittedAt', 'source'];

    /**
     * @param  Collection<string, array<string, mixed>>  $fields
     */
    public function __construct(
        public readonly string $type,
        public readonly string $pageSlug,
        public readonly string $consentText,
        public readonly Collection $fields,
    ) {}

    public static function forType(string $type): ?self
    {
        $section = LandingSection::query()
            ->where('section_key', 'signup')
            ->where('is_active', true)
            ->whereHas('page', fn ($q) => $q->where('is_active', true))
            ->whereHas('contents', fn ($q) => $q->where('content_key', 'signup_type')->where('value', $type))
            ->with(['contents', 'page'])
            ->first();

        if (! $section) {
            return null;
        }

        $content = $section->getContentMap();

        $fields = collect($content)
            ->filter(fn ($value, $key) => preg_match('/^steps\.\d+\.fields$/', $key))
            ->sortKeys(SORT_NATURAL)
            ->flatMap(fn ($json) => json_decode($json, true) ?: [])
            ->filter(fn ($field) => ! empty($field['key']))
            ->keyBy('key');

        return new self($type, $section->page->slug, strip_tags((string) ($content['consent_text'] ?? '')), $fields);
    }

    /**
     * Stored values for a field's options: "starter|Starter" -> "starter".
     *
     * @return array<int, string>
     */
    public static function optionValues(array $field): array
    {
        return collect((array) ($field['options'] ?? []))
            ->map(fn ($option) => is_array($option) ? ($option['value'] ?? '') : explode('|', (string) $option, 2)[0])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    /**
     * Phone numbers are stored in E.164. Kenyan numbers may arrive as
     * "0712 345 678", "712345678" or "254712345678".
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return match (true) {
            str_starts_with(trim($phone), '+') => '+'.$digits,
            str_starts_with($digits, '254') => '+'.$digits,
            default => '+254'.ltrim($digits, '0'),
        };
    }

    /**
     * Normalise input before validation (phone formats, trimmed strings).
     */
    public function prepare(array $input): array
    {
        foreach ($this->fields as $key => $field) {
            if (($field['type'] ?? null) === 'tel' && isset($input[$key]) && is_string($input[$key])) {
                $input[$key] = static::normalizePhone($input[$key]);
            }
        }

        if (isset($input['phone']) && is_string($input['phone'])) {
            $input['phone'] = static::normalizePhone($input['phone']);
        }

        return $input;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [];
        foreach ($this->fields as $key => $field) {
            $rules += $this->rulesFor($key, $field);
        }

        foreach (self::CORE_RULES as $key => $core) {
            $rules[$key] = $core;
        }

        return $rules + self::META_RULES + ['consent' => ['accepted']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->fields->mapWithKeys(fn ($f) => [$f['key'] => strtolower(strip_tags($f['label'] ?? $f['key']))])->all();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(string $key, array $field): array
    {
        $presence = filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'required' : 'nullable';
        $options = static::optionValues($field);
        $choice = $options ? ['string', Rule::in($options)] : ['string', 'max:255'];

        return match ($field['type'] ?? 'text') {
            'multiSelect' => [
                $key => [$presence, 'array'],
                "{$key}.*" => $choice,
            ],
            'email' => [$key => [$presence, 'email', 'max:150']],
            'tel' => [$key => [$presence, 'string', 'regex:/^\+\d{10,15}$/']],
            'select', 'radio', 'singleSelect', 'planCards' => [$key => array_merge([$presence], $choice)],
            'checkbox' => [$key => [$presence, 'boolean']],
            'number' => [$key => [$presence, 'numeric', 'min:0']],
            'textarea' => [$key => [$presence, 'string', 'max:2000']],
            default => [$key => [$presence, 'string', 'max:255']],
        };
    }
}
