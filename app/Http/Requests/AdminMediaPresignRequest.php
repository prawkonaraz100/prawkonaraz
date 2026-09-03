<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminMediaPresignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('kind')) {
            $this->merge([
                'kind' => strtolower((string) $this->input('kind')),
            ]);
        }

        if ($this->has('variant')) {
            $this->merge([
                'variant' => strtolower((string) $this->input('variant')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'kind' => ['required', 'string', 'in:image,video'],
            'mime_type' => ['required', 'string', 'max:100'],
            'bytes' => ['required', 'integer', 'min:1'],
            'variant' => ['required', 'string', 'max:20'],
        ];
    }
}
