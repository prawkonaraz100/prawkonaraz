<?php

test('home page renders the public landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Testy na prawo jazdy 2026')
        ->assertSeeText('Ucz się szybko')
        ->assertSeeText('prawko na raz!')
        ->assertSeeText('Rozpocznij test')
        ->assertSeeText('Kontynuuj z Google')
        ->assertSee('data-home-site-header', false)
        ->assertSee('data-home-header-menu', false)
        ->assertSee('home-site-header--awaiting-pointer', false)
        ->assertSee('aria-label="Otwórz menu konta"', false)
        ->assertSeeText('Cała nauka')
        ->assertSeeText('Każdy błąd')
        ->assertSeeText('Przeglądaj najważniejsze')
        ->assertSeeText('Twoja nauka')
        ->assertSeeText('jedzie z Tobą')
        ->assertDontSeeText('To, co naprawdę pomaga zdać')
        ->assertDontSeeText('Tryb wspólnej nauki')
        ->assertSeeText('Oficjalna baza pytań na prawo jazdy 2026')
        ->assertSeeText('do egzaminu na prawo jazdy!')
        ->assertDontSeeText('Wybierz dostęp i zacznij naukę');
});
