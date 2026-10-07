<?php

namespace App\Http\Requests;

use App\Models\Registration;
use App\Services\Cms\SignupFormSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSignupRequest extends FormRequest
{
    private ?SignupFormSchema $schema = null;

    public function authorize(): bool
    {
        return true;
    }

    public function schema(): ?SignupFormSchema
    {
        $type = (string) $this->input('type');

        if (! in_array($type, Registration::TYPES, true)) {
            return null;
        }

        return $this->schema ??= SignupFormSchema::forType($type);
    }

    public function rules(): array
    {
        $typeRules = ['required', Rule::in(Registration::TYPES)];
        $schema = $this->schema();

        if (! $schema) {
            $typeRules[] = function ($attribute, $value, $fail) {
                $fail('Sign-ups for this audience are not open right now.');
            };

            return ['type' => $typeRules];
        }

        return ['type' => $typeRules] + $schema->rules();
    }

    public function attributes(): array
    {
        return $this->schema()?->attributes() ?? [];
    }
}
