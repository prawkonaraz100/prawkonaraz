<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserReviewRequest extends FormRequest
{
    protected $errorBag = 'review';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $socialLinks = [];

        foreach (['facebook', 'instagram', 'tiktok', 'youtube', 'website'] as $platform) {
            $value = trim((string) $this->input("social_links.{$platform}"));

            if ($value !== '' && ! preg_match('~^https?://~i', $value)) {
                $value = 'https://'.$value;
            }

            $socialLinks[$platform] = $value !== '' ? $value : null;
        }

        $this->merge([
            'content' => trim((string) $this->input('content')),
            'social_links' => $socialLinks,
        ]);
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'min:3', 'max:500'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
                'dimensions:max_width=5000,max_height=5000',
            ],
            'photo_privacy_confirmed' => $this->hasFile('photo')
                ? ['required', 'accepted']
                : ['nullable'],
            'remove_photo' => ['nullable', 'boolean'],
            'social_links' => ['nullable', 'array:facebook,instagram,tiktok,youtube,website'],
            'social_links.facebook' => $this->platformUrlRules('Facebook', ['facebook.com', 'fb.com']),
            'social_links.instagram' => $this->platformUrlRules('Instagram', ['instagram.com']),
            'social_links.tiktok' => $this->platformUrlRules('TikTok', ['tiktok.com']),
            'social_links.youtube' => $this->platformUrlRules('YouTube', ['youtube.com', 'youtu.be']),
            'social_links.website' => ['bail', 'nullable', 'string', 'max:255', 'url:http,https'],
            'return_to' => ['nullable', Rule::in(['home', 'reviews'])],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Wybierz ocenę od 1 do 5 gwiazdek.',
            'rating.between' => 'Ocena musi mieścić się w zakresie od 1 do 5 gwiazdek.',
            'content.required' => 'Napisz kilka zdań o swoim doświadczeniu.',
            'content.min' => 'Opinia powinna mieć co najmniej 3 znaki.',
            'content.max' => 'Opinia może mieć maksymalnie 500 znaków.',
            'photo.image' => 'Wybierz prawidłowy plik graficzny.',
            'photo.mimes' => 'Zdjęcie musi być zapisane jako JPG lub PNG.',
            'photo.max' => 'Zdjęcie może mieć maksymalnie 5 MB.',
            'photo.dimensions' => 'Zdjęcie może mieć maksymalnie 5000 × 5000 pikseli.',
            'photo_privacy_confirmed.required' => 'Potwierdź, że przed wysłaniem zdjęcia zasłoniłeś dane osobowe.',
            'photo_privacy_confirmed.accepted' => 'Potwierdź, że przed wysłaniem zdjęcia zasłoniłeś dane osobowe.',
            'social_links.*.url' => 'Podaj pełny i prawidłowy adres strony.',
            'social_links.*.max' => 'Adres strony może mieć maksymalnie 255 znaków.',
        ];
    }

    /**
     * @param  list<string>  $allowedDomains
     * @return array<int, mixed>
     */
    private function platformUrlRules(string $platformLabel, array $allowedDomains): array
    {
        return [
            'bail',
            'nullable',
            'string',
            'max:255',
            'url:http,https',
            function (string $attribute, mixed $value, \Closure $fail) use ($platformLabel, $allowedDomains): void {
                $host = mb_strtolower((string) parse_url((string) $value, PHP_URL_HOST), 'UTF-8');
                $matchesPlatform = collect($allowedDomains)->contains(
                    fn (string $domain): bool => $host === $domain || str_ends_with($host, '.'.$domain),
                );

                if (! $matchesPlatform) {
                    $fail("Podaj adres profilu w serwisie {$platformLabel}.");
                }
            },
        ];
    }
}
