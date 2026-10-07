<?php

namespace App\Services\Cms;

use App\Models\LandingSection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * The sign-up questions live in the CMS (the "signup" section of /hosts and
 * /business, steps.{i}.fields). This turns that definition into server-side
 * validation so the form, the stored record and the rules never drift.
 */
class SignupFormSchema
{
    /** Fields every sign-up must carry, whatever the CMS says. */
    private const CORE_RULES = [
        'first_name' => ['required', 'string', 'max:50'],
        'email' => ['required', 'email', 'max:150'],
    ];

    /**
     * @param  Collection<int, array<string, mixed>>  $fields
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

        return new self($type, $section->page->slug, (string) ($content['consent_text'] ?? ''), $fields);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = $this->fields->map(fn (array $field) => $this->rulesFor($field))->all();

        foreach (self::CORE_RULES as $key => $core) {
            $rules[$key] = $core;
        }

        $rules['consent'] = ['accepted'];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->fields->mapWithKeys(fn ($f) => [$f['key'] => strtolower($f['label'] ?? $f['key'])])->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function rulesFor(array $field): array
    {
        $rules = [filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'required' : 'nullable'];
        $options = array_values(array_filter((array) ($field['options'] ?? []), 'strlen'));

        return array_merge($rules, match ($field['type'] ?? 'text') {
            'email' => ['email', 'max:150'],
            'tel' => ['string', 'max:20', 'regex:/^[0-9+()\-\s]{6,20}$/'],
            'select', 'radio' => $options ? ['string', Rule::in($options)] : ['string', 'max:255'],
            'checkbox' => ['boolean'],
            'number' => ['numeric', 'min:0'],
            'textarea' => ['string', 'max:2000'],
            default => ['string', 'max:255'],
        });
    }
}
