<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RankedMatchAnswerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('user_answer')) {
            $this->merge([
                'user_answer' => strtolower((string) $this->input('user_answer')),
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
            'user_answer' => ['required', 'string', 'in:a,b,c'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ];
    }
}
