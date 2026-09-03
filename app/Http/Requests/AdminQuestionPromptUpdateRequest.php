<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminQuestionPromptUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('prompt')) {
            return;
        }

        $this->merge([
            'prompt' => trim((string) $this->input('prompt')),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string'],
            'apply_scope' => ['nullable', 'string', 'in:single,shared_external_id'],
        ];
    }
}
