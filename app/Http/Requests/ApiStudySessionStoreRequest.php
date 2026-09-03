<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiStudySessionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if (! $this->has('category_id') && $this->has('license_category_id')) {
            $payload['category_id'] = $this->input('license_category_id');
        }

        foreach (['mode', 'ui_shell', 'question_scope', 'question_status', 'question_count_strategy'] as $key) {
            if ($this->has($key) && $this->input($key) !== null) {
                $payload[$key] = strtolower((string) $this->input($key));
            }
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:license_categories,id'],
            'license_category_id' => ['nullable', 'integer', 'exists:license_categories,id'],
            'mode' => ['required', 'string', 'in:exam,learn,review,sr_review,hard,quick'],
            'ui_shell' => ['nullable', 'string', 'in:exam,exam_like,zen'],
            'question_scope' => ['nullable', 'string', 'in:all,basic,specialist'],
            'question_count' => ['nullable', 'integer', 'min:1'],
            'question_topic_id' => ['nullable', 'integer', 'exists:question_topics,id'],
            'question_status' => ['nullable', 'string', 'in:all,unanswered,memorized,incorrect,correct,mistake_list'],
            'randomize_order' => ['nullable', 'boolean'],
            'question_count_strategy' => ['nullable', 'string', 'in:fixed,topic_remaining'],
            'replace_active_session' => ['nullable', 'boolean'],
        ];
    }
}
