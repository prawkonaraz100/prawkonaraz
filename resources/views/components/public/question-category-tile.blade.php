@props(['category', 'description'])

<a
    href="{{ $category['url'] }}"
    class="question-database-guide__tile"
    aria-label="Przejdź do pytań kategorii {{ $category['code'] }}"
>
    <span class="question-database-guide__tile-icon">
        <x-questions.vehicle-icon :code="$category['code']" />
    </span>
    <strong>{{ $category['title'] }}</strong>
    <span class="question-database-guide__tile-description">{{ $description }}</span>
    <small>{{ number_format($category['questions_count'], 0, ',', ' ') }} pytań</small>
    <span class="question-database-guide__tile-link">Sprawdź pytania <span aria-hidden="true">→</span></span>
</a>
