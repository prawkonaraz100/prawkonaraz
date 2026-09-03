<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class AdminQuestionUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['correct_answer', 'question_type'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = strtolower((string) $this->input($field));
            }
        }

        if ($this->has('category_code')) {
            $normalized['category_code'] = strtoupper((string) $this->input('category_code'));
        }

        if ($this->has('media') && is_array($this->input('media'))) {
            $normalized['media'] = collect($this->input('media'))
                ->map(function ($item) {
                    if (! is_array($item)) {
                        return $item;
                    }

                    foreach (['kind', 'variant'] as $field) {
                        if (isset($item[$field])) {
                            $item[$field] = strtolower((string) $item[$field]);
                        }
                    }

                    return $item;
                })
                ->all();
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['nullable', 'integer', 'exists:questions,id'],
            'license_category_id' => ['nullable', 'integer', 'exists:license_categories,id', 'required_without_all:question_id,category_code'],
            'category_code' => ['nullable', 'string', 'max:50', 'exists:license_categories,code', 'required_without_all:question_id,license_category_id'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'in:a,b,c'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'points' => ['nullable', 'integer', 'min:1', 'max:3'],
            'question_type' => ['nullable', 'string', 'in:single_choice,boolean'],
            'is_active' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'media' => ['sometimes', 'array'],
            'media.*.kind' => ['nullable', 'string', 'in:image,video'],
            'media.*.disk' => ['nullable', 'string', 'max:50'],
            'media.*.path' => ['required', 'string', 'max:2048', 'distinct'],
            'media.*.poster_path' => ['nullable', 'string', 'max:2048'],
            'media.*.mime_type' => ['nullable', 'string', 'max:100'],
            'media.*.bytes' => ['nullable', 'integer', 'min:1'],
            'media.*.duration_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'media.*.width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'media.*.height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'media.*.variant' => ['nullable', 'string', 'max:20'],
            'media.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'media.*.metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            $questionType = (string) ($this->input('question_type') ?? 'single_choice');
            $correctAnswer = (string) ($this->input('correct_answer') ?? '');
            $optionC = $this->input('option_c');

            if ($questionType === 'boolean' && $correctAnswer === 'c') {
                $validator->errors()->add('correct_answer', 'Boolean questions can only use answers A or B.');
            }

            if ($questionType !== 'boolean' && $correctAnswer === 'c' && blank($optionC)) {
                $validator->errors()->add('option_c', 'Option C is required when correct_answer is C.');
            }

            foreach ((array) $this->input('media', []) as $index => $media) {
                if (! is_array($media)) {
                    continue;
                }

                $kind = (string) ($media['kind'] ?? 'image');
                $variant = (string) ($media['variant'] ?? 'full');
                $mimeType = $media['mime_type'] ?? null;

                if ($mimeType !== null && ! in_array((string) $mimeType, (array) config("media.allowed_mime_types.{$kind}", []), true)) {
                    $validator->errors()->add("media.{$index}.mime_type", 'Unsupported MIME type for the selected media kind.');
                }

                if (! in_array($variant, (array) config("media.allowed_variants.{$kind}", []), true)) {
                    $validator->errors()->add("media.{$index}.variant", 'Unsupported media variant for the selected media kind.');
                }

                $maxBytes = (int) config("media.max_bytes.{$kind}", 0);

                if ($maxBytes > 0 && isset($media['bytes']) && (int) $media['bytes'] > $maxBytes) {
                    $validator->errors()->add("media.{$index}.bytes", 'Media asset exceeds the configured size limit.');
                }

                if ($kind !== 'video' && filled($media['poster_path'] ?? null)) {
                    $validator->errors()->add("media.{$index}.poster_path", 'Poster path can only be set for video assets.');
                }
            }
        });
    }
}
