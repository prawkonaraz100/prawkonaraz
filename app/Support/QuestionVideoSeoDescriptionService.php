<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuestionVideoSeoDescriptionService
{
    protected const MAX_EDITORIAL_DESCRIPTION_CHARS = 2048;

    protected const MAX_DESCRIPTION_CHARS = 320;

    public function __construct(
        protected QuestionTextFormatter $questionTextFormatter,
    ) {}

    public function forQuestion(Question $question): string
    {
        $editorialDescription = $this->editorialDescriptionForLoadedPrimaryVideo($question);

        if ($editorialDescription !== null) {
            return $editorialDescription;
        }

        $displayExternalId = $this->displayExternalId($question->external_id);
        $categoryLabel = $this->categoryLabel($question);
        $prompt = $this->questionTopic($question);
        $template = $this->templateFor($question, $displayExternalId, $categoryLabel);
        $description = trim($template.' Temat sceny: '.$prompt);

        if (! str_ends_with($description, '.')) {
            $description .= '.';
        }

        return Str::limit($description, self::MAX_DESCRIPTION_CHARS - 3, '...');
    }

    protected function editorialDescriptionForLoadedPrimaryVideo(Question $question): ?string
    {
        if (! $question->relationLoaded('media')) {
            return null;
        }

        /** @var Collection<int, QuestionMedia> $media */
        $media = $question->media instanceof Collection
            ? $question->media
            : Collection::make($question->media ?? []);

        $video = $media
            ->filter(fn (QuestionMedia $media): bool => $media->kind === 'video')
            ->sortBy(fn (QuestionMedia $media): string => sprintf(
                '%d:%010d:%010d',
                (string) $media->variant === 'full' ? 0 : 1,
                (int) $media->sort_order,
                (int) $media->getKey(),
            ))
            ->first();

        if (! $video instanceof QuestionMedia) {
            return null;
        }

        return $this->validEditorialDescription($video->seo_video_description);
    }

    protected function validEditorialDescription(mixed $description): ?string
    {
        if (! is_string($description) && ! is_numeric($description)) {
            return null;
        }

        $description = Str::squish((string) $description);

        if ($description === '' || mb_strlen($description) > self::MAX_EDITORIAL_DESCRIPTION_CHARS) {
            return null;
        }

        return $description;
    }

    protected function templateFor(Question $question, string $displayExternalId, string $categoryLabel): string
    {
        $categoryContext = $categoryLabel !== '' ? ' dla '.$categoryLabel : '';

        $templates = [
            'Materiał wideo do pytania '.$displayExternalId.$categoryContext.' pokazuje krótką sytuację drogową z egzaminu teoretycznego i pomaga przeanalizować ją na podstawie obserwacji oraz zasad ruchu.',
            'Nagranie przypisane do pytania '.$displayExternalId.$categoryContext.' przedstawia scenę z perspektywy kierującego, z naciskiem na ocenę otoczenia, znaków i zachowania uczestników ruchu.',
            'Film przy pytaniu '.$displayExternalId.$categoryContext.' prezentuje praktyczny kontekst drogowy wykorzystywany w nauce do prawa jazdy i porządkuje to, na co warto zwrócić uwagę w kadrze.',
            'Klip wideo dla pytania '.$displayExternalId.$categoryContext.' pokazuje sytuację egzaminacyjną na drodze, w której liczy się spokojna obserwacja, rozpoznanie zagrożeń i właściwa decyzja kierowcy.',
            'Wideo powiązane z pytaniem '.$displayExternalId.$categoryContext.' przedstawia realny układ drogowy z pytania egzaminacyjnego, pomagając skupić uwagę na przebiegu sceny i warunkach ruchu.',
        ];

        $index = (int) (abs(crc32((string) $question->external_id.'|'.(string) $question->prompt)) % count($templates));

        return $templates[$index];
    }

    protected function questionTopic(Question $question): string
    {
        $prompt = Str::squish($this->questionTextFormatter->plainText($question->prompt));
        $prompt = trim($prompt);

        if ($prompt === '') {
            return 'analiza sytuacji drogowej pokazanej w materiale wideo';
        }

        $prompt = rtrim($prompt, " \t\n\r\0\x0B?.!");
        $prompt = Str::lower(mb_substr($prompt, 0, 1)).mb_substr($prompt, 1);

        if (Str::startsWith($prompt, 'czy ')) {
            return 'ocena, '.$prompt;
        }

        return Str::limit($prompt, 150, '...');
    }

    protected function categoryLabel(Question $question): string
    {
        $code = trim((string) $question->licenseCategory?->code);

        return $code !== '' ? 'kategorii '.$code : '';
    }

    protected function displayExternalId(mixed $externalId): string
    {
        $value = trim((string) $externalId);

        if ($value === '') {
            return 'egzaminacyjnego';
        }

        if (str_contains($value, ':')) {
            $suffix = trim(Str::afterLast($value, ':'));

            return $suffix !== '' ? $suffix : $value;
        }

        return $value;
    }
}
