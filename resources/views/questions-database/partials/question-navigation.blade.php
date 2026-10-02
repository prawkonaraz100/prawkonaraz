<nav class="question-detail__navigation" aria-label="Nawigacja po pytaniach"
    data-public-question-keyboard-navigation
    data-previous-url="{{ $hasPreviousQuestion ? $previousQuestionUrl : '' }}"
    data-next-url="{{ $hasNextQuestion ? $nextQuestionUrl : '' }}">
    <a href="{{ $previousQuestionUrl }}"><span aria-hidden="true">←</span> {{ $hasPreviousQuestion ? 'Poprzednie pytanie' : 'Wróć do bazy' }}</a>
    <span class="question-detail__position">
        @if ($currentPosition !== null && $navigationTotal > 0)
            {{ number_format($currentPosition, 0, ',', ' ') }} / {{ number_format($navigationTotal, 0, ',', ' ') }}
        @endif
    </span>
    <a href="{{ $nextQuestionUrl }}" class="question-detail__next">{{ $hasNextQuestion ? 'Następne pytanie' : 'Wróć do bazy' }} <span aria-hidden="true">→</span></a>
</nav>
