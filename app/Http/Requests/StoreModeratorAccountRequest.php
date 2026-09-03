<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ModeratorAccountProvisioningService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModeratorAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isModerator();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_type' => [
                'required',
                Rule::in([
                    ModeratorAccountProvisioningService::ACCOUNT_TYPE_FULL,
                    ModeratorAccountProvisioningService::ACCOUNT_TYPE_TEMPORARY,
                ]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'exclude_if:account_type,'.ModeratorAccountProvisioningService::ACCOUNT_TYPE_TEMPORARY,
                'required_if:account_type,'.ModeratorAccountProvisioningService::ACCOUNT_TYPE_FULL,
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'target_category_id' => [
                'required',
                'integer',
                Rule::exists('license_categories', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Konto z takim adresem e-mail już istnieje.',
            'email.required_if' => 'Podaj e-mail użytkownika albo wybierz konto tymczasowe.',
            'target_category_id.required' => 'Wybierz kategorię nauki dla kursanta.',
        ];
    }
}
