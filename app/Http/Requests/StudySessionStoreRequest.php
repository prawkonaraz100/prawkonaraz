<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudySessionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'license_category_id' => ['required', 'integer', 'exists:license_categories,id'],
            'mode' => ['required', 'string', 'in:exam,learn,review,sr_review,hard,quick,pjm'],
            'ui_shell' => ['nullable', 'string', 'in:exam,exam_like,zen'],
            'question_scope' => ['nullable', 'string', 'in:all,basic,specialist'],
            'question_count' => ['required', 'integer', 'min:1'],
            'question_topic_id' => ['nullable', 'integer', 'exists:question_topics,id'],
            'question_status' => ['nullable', 'string', 'in:all,unanswered,memorized,incorrect,correct,mistake_list'],
            'randomize_order' => ['nullable', 'boolean'],
        ];
    }
}
