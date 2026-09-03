<?php

namespace App\Http\Requests;

use App\Models\LegalUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminLegalContentQuestionReferenceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdministrator();
    }

    protected function prepareForValidation(): void
    {
        $externalIds = collect($this->input('external_ids', []))
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $publicNotes = collect($this->input('public_notes', []))
            ->mapWithKeys(fn (mixed $note, mixed $externalId): array => [
                trim((string) $externalId) => filled($note) ? trim((string) $note) : null,
            ])
            ->all();

        $this->merge([
            'external_ids' => $externalIds,
            'public_notes' => $publicNotes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_ids' => ['required', 'array', 'min:1', 'max:50'],
            'external_ids.*' => ['required', 'string', 'max:255', 'distinct'],
            'legal_unit_id' => [
                'required',
                'integer',
                Rule::exists('legal_units', 'id')->where('status', LegalUnit::STATUS_VERIFIED),
            ],
            'public_notes' => ['sometimes', 'array'],
            'public_notes.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
