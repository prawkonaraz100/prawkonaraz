<?php

namespace App\Support;

class LearningProgressMessageService
{
    /** @return array{state:string,title:string,message:string,action:string,icon:string} */
    public function forCategory(array $progress, int $pendingReviewCount, ?array $topicCompletion = null): array
    {
        $total = (int) ($progress['total_questions'] ?? 0);
        $answered = (int) ($progress['answered_questions'] ?? 0);
        $unanswered = (int) ($progress['unanswered_questions'] ?? 0);
        $topics = (int) ($progress['total_topics'] ?? 0);
        $completed = (int) ($progress['completed_topics'] ?? 0);
        $allCompleted = $topics > 0 && $completed >= $topics;

        if ($total <= 0) {
            return $this->message('empty', 'Brak pytań', 'Brak dostępnych pytań dla tej kategorii.', 'none');
        }
        if ($answered <= 0) {
            return $this->message('not_started', 'Zacznij od pierwszego działu', 'Wybierz dział i rozpocznij naukę. Zaliczasz go pełną bezbłędną sesją.', 'classic');
        }
        if ($topicCompletion !== null) {
            $name = $topicCompletion['name'];

            return $this->message('topic_completed', 'Gratulacje! Dział zaliczony', "Dział „{$name}” został ukończony bez błędu. Zaliczone działy: {$completed} / {$topics}.", $allCompleted && $unanswered === 0 ? 'exam' : 'classic');
        }
        if ($unanswered > 0) {
            return $this->message('in_progress', $allCompleted ? 'Przerób nowe pytania' : 'Kontynuuj naukę', "Nieprzerobione pytania: {$unanswered}. Przerobienie bazy i zaliczenie działów to osobne etapy.", 'classic');
        }
        if (! $allCompleted) {
            $remaining = max($topics - $completed, 0);

            return $this->message('topics_pending', 'Zalicz kolejne działy', "Pozostało działów do zaliczenia: {$remaining}. Przejdź wszystkie pytania działu w jednej sesji bez błędu.", 'classic');
        }

        return $this->message('ready', 'Świetna robota!', 'Wszystkie działy zaliczone. Sprawdź się w egzaminie próbnym.', 'exam');
    }

    /** @return array{state:string,title:string,message:string,action:string,icon:string} */
    public function forProfessionalCourse(array $progress, int $incorrectCount): array
    {
        $total = (int) ($progress['total_questions'] ?? 0);
        $answered = (int) ($progress['answered_count'] ?? 0);
        if ($total <= 0) {
            return $this->message('empty', 'Brak pytań w kursie', 'Nie ma dostępnych pytań w modułach tego kursu.', 'none');
        }
        if ($answered <= 0) {
            return $this->message('not_started', 'Zacznij kurs zawodowy', 'Wybierz pierwszy moduł. Procent pokazuje przerobione pytania, nie zaliczenie kursu.', 'classic');
        }
        if ($answered < $total) {
            $remaining = $total - $answered;

            return $this->message('in_progress', 'Kontynuuj kurs zawodowy', "Nieprzerobione pytania: {$remaining}. Ucz się moduł po module.", 'classic');
        }

        return $this->message('covered', 'Cała baza kursu przerobiona', 'Utrwal wiedzę w kolejnej sesji. Ten procent nie jest wynikiem egzaminu ani zaliczeniem kursu.', 'classic');
    }

    private function message(string $state, string $title, string $message, string $action): array
    {
        $icon = match ($state) {
            'not_started' => 'book',
            'in_progress' => 'arrow',
            'topics_pending' => 'clipboard',
            'topic_completed', 'ready' => 'trophy',
            'covered' => 'check',
            default => 'info',
        };

        return compact('state', 'title', 'message', 'action', 'icon');
    }
}
