<?php

test('home page renders the public landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy 2026')
        ->assertSeeText('Rozpocznij Speedrun')
        ->assertDontSeeText('To, co naprawdę pomaga zdać')
        ->assertDontSeeText('Tryb wspólnej nauki')
        ->assertDontSeeText('Oficjalna baza pytań na prawo jazdy')
        ->assertDontSeeText('Wybierz dostęp i zacznij naukę');
});
