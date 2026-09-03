<?php

use App\Models\LegalAct;
use App\Models\LegalUnit;
use App\Models\User;

test('admin can search legal units and see review status', function () {
    $admin = User::factory()->admin()->create();
    $act = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym',
        'title' => 'Ustawa z dnia 20 czerwca 1997 r. - Prawo o ruchu drogowym',
        'short_title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);

    $verifiedUnit = LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'type' => 'section',
        'label' => 'art. 26 ust. 6',
        'canonical_path' => '26/6',
        'slug' => 'art-26-ust-6',
        'title' => 'Przystanek tramwajowy bez wysepki',
        'official_excerpt' => 'Kierujący jest obowiązany zatrzymać pojazd.',
        'source_url' => 'https://eli.gov.pl/eli/DU/1997/602/ogl#arti_26-pass_6',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    $reviewUnit = LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'type' => 'point',
        'label' => 'art. 26 ust. 3 pkt 1',
        'canonical_path' => '26/3/1',
        'slug' => 'art-26-ust-3-pkt-1',
        'title' => 'Wyprzedzanie przy przejściu dla pieszych',
        'source_url' => 'https://eli.gov.pl/eli/DU/1997/602/ogl#arti_26-pass_3-pint_1',
        'status' => LegalUnit::STATUS_NEEDS_REVIEW,
    ]);
    LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'type' => 'article',
        'label' => 'art. 99',
        'canonical_path' => '99',
        'slug' => 'art-99',
        'title' => 'Ukryta odrzucona jednostka',
        'source_url' => 'https://eli.gov.pl/eli/DU/1997/602/ogl#arti_99',
        'status' => LegalUnit::STATUS_REJECTED,
    ]);

    $this->actingAs($admin)
        ->getJson(route('api.v1.admin.legal-units.search', ['q' => 'art. 26']))
        ->assertOk()
        ->assertJsonCount(2, 'data.legal_units')
        ->assertJsonPath('data.legal_units.0.id', $verifiedUnit->getKey())
        ->assertJsonPath('data.legal_units.0.can_select', true)
        ->assertJsonPath('data.legal_units.0.status_label', 'zweryfikowany')
        ->assertJsonPath('data.legal_units.1.id', $reviewUnit->getKey())
        ->assertJsonPath('data.legal_units.1.can_select', false)
        ->assertJsonPath('data.legal_units.1.status_label', 'do review');
});

test('legal unit search requires administrator', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.admin.legal-units.search', ['q' => 'art. 26']))
        ->assertForbidden();
});
