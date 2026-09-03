<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertUserProductProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'target_category_id' => ['nullable', 'integer', 'exists:license_categories,id'],
            'exam_date' => ['nullable', 'date'],
            'onboarding_step' => ['nullable', 'string', 'max:100'],
            'visual_explanations_enabled' => ['nullable', 'boolean'],
            'auto_remove_incorrect_questions_on_correct' => ['nullable', 'boolean'],
            'visual_explanations_mode' => ['nullable', 'string', Rule::in([
                'before_answer',
                'after_incorrect',
                'off',
            ])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('target_category_id')) {
                return;
            }

            $user = $this->user();

            if (! $user instanceof User || $user->canUseAllStudyCategories()) {
                return;
            }

            $user->loadMissing('profile:user_id,target_category_id');
            $currentCategoryId = $user->profile?->target_category_id;
            $requestedCategoryId = $this->integer('target_category_id') ?: null;

            if ($currentCategoryId === null && $requestedCategoryId !== null) {
                return;
            }

            if ((int) $currentCategoryId === (int) $requestedCategoryId) {
                return;
            }

            $validator->errors()->add(
                'target_category_id',
                'Kategoria nauki jest przypisana na stałe. Jeśli musisz ją zmienić, skontaktuj się z administratorem.',
            );
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('visual_explanations_mode')) {
            return;
        }

        if (! $this->has('visual_explanations_enabled')) {
            return;
        }

        $enabled = filter_var($this->input('visual_explanations_enabled'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($enabled === null) {
            return;
        }

        $this->merge([
            'visual_explanations_mode' => $enabled ? 'after_incorrect' : 'off',
        ]);
    }
}
