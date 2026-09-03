<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSeoTopic;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PublicQuestionSeoService
{
    public function __construct(
        protected QuestionTextFormatter $questionTextFormatter,
        protected PublicUrlResolver $publicUrlResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function hub(int $categoriesCount, int $questionsCount): array
    {
        $currentYear = now()->year;

        return [
            'title' => "Oficjalna baza pytań na prawo jazdy {$currentYear} - pytania z odpowiedziami",
            'description' => "Przeglądaj bazę {$questionsCount} oficjalnych pytań egzaminacyjnych uporządkowanych według {$categoriesCount} kategorii prawa jazdy. Wyszukuj pytania po numerze oraz sprawdzaj prawidłowe odpowiedzi i wyjaśnienia.",
            'canonical' => route('public.questions.hub'),
            'image' => $this->publicUrlResolver->normalize('/images/questions/question-database-hero.png'),
            'image_alt' => 'Samochód nauki jazdy i ekran z przykładowym pytaniem egzaminacyjnym',
            'image_width' => 576,
            'image_height' => 383,
            'preload_image' => asset('images/questions/question-database-hero.png'),
            'og_type' => 'website',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function category(LicenseCategory $category, LengthAwarePaginator $questions): array
    {
        $currentYear = now()->year;
        $canonical = route('public.questions.category', $category->slug);
        $pageSuffix = '';

        if ($questions->currentPage() > 1) {
            $canonical .= '?page='.$questions->currentPage();
            $pageSuffix = ' - strona '.$questions->currentPage();
        }

        return [
            'title' => "Pytania na prawo jazdy kat. {$category->code} {$currentYear} - oficjalna baza{$pageSuffix}",
            'description' => "Oficjalne pytania egzaminacyjne kategorii {$category->code}. {$questions->total()} pytań z poprawnymi odpowiedziami i wyjaśnieniami{$pageSuffix}.",
            'canonical' => $canonical,
            'og_type' => 'website',
        ];
    }

    /**
     * @param  array<string, mixed>  $mediaPayload
     * @return array<string, mixed>
     */
    public function question(
        Question $question,
        ?LicenseCategory $primaryCategory,
        array $mediaPayload,
        string $canonicalUrl,
    ): array {
        $plainPrompt = $this->questionTextFormatter->plainText($question->prompt);
        $titleLead = rtrim(Str::limit(Str::squish($plainPrompt), 82, '...'), '.');
        $categorySuffix = $primaryCategory instanceof LicenseCategory
            ? " kat. {$primaryCategory->code}"
            : '';
        $displayExternalId = $this->displayExternalId($question->external_id);
        $title = trim("{$titleLead} - pytanie {$displayExternalId}{$categorySuffix}");
        $descriptionLead = rtrim(Str::limit(Str::squish($plainPrompt), 110, '...'), '.');
        $descriptionPrefix = $primaryCategory instanceof LicenseCategory
            ? "Oficjalne pytanie na prawo jazdy kategorii {$primaryCategory->code}."
            : 'Oficjalne pytanie egzaminacyjne na prawo jazdy.';
        $image = $mediaPayload['poster_url'] ?? $mediaPayload['url'] ?? null;
        $imageAlt = $mediaPayload['alt_text'] ?? "Pytanie egzaminacyjne {$displayExternalId}";

        return [
            'title' => $title,
            'description' => Str::limit("{$descriptionPrefix} {$descriptionLead} Sprawdź poprawną odpowiedź i wyjaśnienie.", 160),
            'canonical' => $canonicalUrl,
            'image' => $image,
            'image_alt' => $imageAlt,
            'image_width' => $mediaPayload['width'] ?? null,
            'image_height' => $mediaPayload['height'] ?? null,
            'preload_image' => $mediaPayload['kind'] === 'image'
                ? ($mediaPayload['url'] ?? null)
                : ($mediaPayload['poster_url'] ?? null),
            'og_type' => 'article',
            'published_time' => $question->published_at?->toIso8601String(),
            'modified_time' => $question->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public function topic(QuestionSeoTopic $topic, LengthAwarePaginator $questions): array
    {
        $canonical = route('public.questions.topics.show', $topic->slug);
        $pageSuffix = '';

        if ($questions->currentPage() > 1) {
            $canonical .= '?page='.$questions->currentPage();
            $pageSuffix = ' — strona '.$questions->currentPage();
        }

        return [
            'title' => Str::limit("{$topic->label} — pytania na prawo jazdy{$pageSuffix}", 60, ''),
            'description' => Str::limit("{$topic->description} {$questions->total()} oficjalnych pytań z odpowiedziami i wyjaśnieniami{$pageSuffix}.", 160),
            'canonical' => $canonical,
            'og_type' => 'website',
        ];
    }

    protected function displayExternalId(mixed $externalId): string
    {
        $value = trim((string) $externalId);

        if ($value === '') {
            return '';
        }

        if (str_contains($value, ':')) {
            $suffix = trim(Str::afterLast($value, ':'));

            return $suffix !== '' ? $suffix : $value;
        }

        return $value;
    }
}
