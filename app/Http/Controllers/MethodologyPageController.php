<?php

namespace App\Http\Controllers;

use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\View\View;

class MethodologyPageController extends Controller
{
    public function __invoke(
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
    ): View {
        $breadcrumbs = $trafficSignBreadcrumbs->methodology();

        return view('about.methodology', [
            'meta' => $trafficSignSeoService->methodologyPage(),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->methodologyPage($breadcrumbs),
            'learningSteps' => [
                [
                    'number' => '01',
                    'title' => 'Najpierw zobacz sytuację',
                    'body' => 'Pytanie, zdjęcie lub film pomagają wychwycić to, co naprawdę decyduje o odpowiedzi.',
                ],
                [
                    'number' => '02',
                    'title' => 'Zrozum, dlaczego odpowiedź jest właściwa',
                    'body' => 'Po odpowiedzi dostajesz wyjaśnienie: co mówi zasada, na jaki detal zwrócić uwagę i gdzie łatwo popełnić błąd.',
                ],
                [
                    'number' => '03',
                    'title' => 'Wróć do tego, co sprawia trudność',
                    'body' => 'Błędne pytania możesz powtarzać, aż odpowiedź będzie pewna. Dzięki temu nie tracisz czasu na to, co już umiesz.',
                ],
            ],
            'learningPrinciples' => [
                [
                    'title' => 'Oficjalna baza pytań',
                    'body' => 'Uczysz się na pytaniach pochodzących z oficjalnej państwowej bazy.',
                ],
                [
                    'title' => 'Materiały sprawdzane na bieżąco',
                    'body' => 'Gdy zmieniają się przepisy lub materiały egzaminacyjne, sprawdzamy, co trzeba uaktualnić.',
                ],
                [
                    'title' => 'Wyjaśnienia prostym językiem',
                    'body' => 'Nie zostawiamy Cię z samym „tak” albo „nie”. Chcemy, abyś wiedział, jak podjąć dobrą decyzję na drodze.',
                ],
            ],
        ]);
    }
}
