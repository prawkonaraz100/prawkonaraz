<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminMediaReorderRequest extends FormRequest
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
            'media_ids' => ['required', 'array', 'min:1'],
            'media_ids.*' => ['required', 'integer', 'distinct', 'exists:question_media,id'],
        ];
    }
}
