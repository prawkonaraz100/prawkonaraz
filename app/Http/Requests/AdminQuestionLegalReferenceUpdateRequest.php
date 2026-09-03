<?php

namespace App\Http\Requests;

use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminQuestionLegalReferenceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdministrator();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_id' => filled($this->input('reference_id')) ? $this->input('reference_id') : null,
            'public_note' => filled($this->input('public_note'))
                ? trim((string) $this->input('public_note'))
                : null,
            'legal_topic_id' => filled($this->input('legal_topic_id'))
                ? $this->input('legal_topic_id')
                : null,
            'legal_content_page_id' => filled($this->input('legal_content_page_id'))
                ? $this->input('legal_content_page_id')
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference_id' => ['nullable', 'integer', Rule::exists('question_legal_references', 'id')],
            'public_note' => ['nullable', 'string'],
            'legal_unit_id' => [
                'required',
                'integer',
                Rule::exists('legal_units', 'id')->where('status', LegalUnit::STATUS_VERIFIED),
            ],
            'legal_topic_id' => [
                'nullable',
                'integer',
                Rule::exists('legal_topics', 'id')->where('status', LegalTopic::STATUS_PUBLISHED),
            ],
            'legal_content_page_id' => [
                'nullable',
                'integer',
                Rule::exists('legal_content_pages', 'id')->where(
                    fn ($query) => $query
                        ->where('status', LegalContentPage::STATUS_PUBLISHED)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now()),
                ),
            ],
        ];
    }
}
