<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactMessageRequest extends FormRequest
{
    /**
     * Keep contact errors separate from authentication forms rendered on the homepage.
     */
    protected $errorBag = 'contact';

    public const TOPICS = [
        'learning' => 'Pytanie o naukę',
        'technical' => 'Problem techniczny',
        'access' => 'Płatność lub dostęp',
        'cooperation' => 'Współpraca',
        'other' => 'Inna sprawa',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_email' => ['required', 'string', 'email:rfc', 'max:254'],
            'contact_topic' => ['required', Rule::in(array_keys(self::TOPICS))],
            'contact_message' => ['required', 'string', 'min:10', 'max:5000'],
            'contact_consent' => ['accepted'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_name.required' => 'Wpisz swoje imię.',
            'contact_name.max' => 'Imię może mieć maksymalnie 100 znaków.',
            'contact_email.required' => 'Wpisz adres e-mail.',
            'contact_email.email' => 'Wpisz poprawny adres e-mail.',
            'contact_email.max' => 'Adres e-mail jest zbyt długi.',
            'contact_topic.required' => 'Wybierz temat wiadomości.',
            'contact_topic.in' => 'Wybierz temat z listy.',
            'contact_message.required' => 'Napisz, w czym możemy pomóc.',
            'contact_message.min' => 'Wiadomość powinna mieć co najmniej 10 znaków.',
            'contact_message.max' => 'Wiadomość może mieć maksymalnie 5000 znaków.',
            'contact_consent.accepted' => 'Potwierdź zapoznanie się z Polityką prywatności.',
            'website.max' => 'Nie udało się wysłać wiadomości.',
        ];
    }

    public function topicLabel(): string
    {
        return self::TOPICS[(string) $this->validated('contact_topic')];
    }
}
