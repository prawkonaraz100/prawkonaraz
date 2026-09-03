<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminQuestionPublicExplanationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdministrator();
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        if ($this->has('body')) {
            $updates['body'] = trim((string) $this->input('body'));
        }

        if ($this->has('exam_trap')) {
            $updates['exam_trap'] = trim((string) $this->input('exam_trap'));
        }

        if ($this->has('dont_confuse_with')) {
            $updates['dont_confuse_with'] = trim((string) $this->input('dont_confuse_with'));
        }

        if ($this->has('common_mistakes')) {
            $updates['common_mistakes'] = collect($this->input('common_mistakes'))
                ->filter(fn (mixed $mistake): bool => is_array($mistake))
                ->map(fn (array $mistake): array => [
                    'title' => trim((string) ($mistake['title'] ?? '')),
                    'explanation' => trim((string) ($mistake['explanation'] ?? '')),
                ])
                ->filter(fn (array $mistake): bool => $mistake['title'] !== '' || $mistake['explanation'] !== '')
                ->values()
                ->all();
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'dont_confuse_with' => ['nullable', 'string'],
            'exam_trap' => ['nullable', 'string'],
            'common_mistakes' => ['nullable', 'array', 'max:8'],
            'common_mistakes.*.title' => ['required', 'string', 'max:500'],
            'common_mistakes.*.explanation' => ['required', 'string'],
        ];
    }
}
