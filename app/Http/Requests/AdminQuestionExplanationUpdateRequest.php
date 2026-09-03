<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminQuestionExplanationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('explanation')) {
            return;
        }

        $explanation = trim((string) $this->input('explanation'));

        $this->merge([
            'explanation' => $explanation === '' ? null : $explanation,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'explanation' => ['nullable', 'string'],
            'apply_scope' => ['sometimes', 'string', 'in:single,shared_external_id'],
        ];
    }
}
