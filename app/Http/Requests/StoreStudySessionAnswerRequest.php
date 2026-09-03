<?php

namespace App\Http\Requests;

use App\Support\StudySessionAnswerKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudySessionAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $answerKind = strtolower((string) $this->input('answer_kind', StudySessionAnswerKind::CHOICE));
        $payload = [
            'answer_kind' => $answerKind,
        ];

        if ($this->has('selected_answer') && $this->input('selected_answer') !== null) {
            $payload['selected_answer'] = strtolower((string) $this->input('selected_answer'));
        }

        $this->merge($payload);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'answer_kind' => ['required', 'string', Rule::in([
                StudySessionAnswerKind::CHOICE,
                StudySessionAnswerKind::UNKNOWN,
            ])],
            'selected_answer' => ['required_if:answer_kind,'.StudySessionAnswerKind::CHOICE, 'nullable', 'string', 'in:a,b,c'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ];
    }
}
