<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminMediaConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'upload_token' => ['required', 'string', 'max:64'],
            'poster_upload_token' => ['nullable', 'string', 'max:64'],
            'width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
