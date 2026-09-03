<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinRankedQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:license_categories,id'],
        ];
    }
}
