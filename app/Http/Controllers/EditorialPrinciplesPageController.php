<?php

namespace App\Http\Controllers;

use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\View\View;

class EditorialPrinciplesPageController extends Controller
{
    public function __invoke(
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
    ): View {
        $breadcrumbs = $trafficSignBreadcrumbs->editorialPrinciples();

        return view('about.editorial-principles', [
            'meta' => $trafficSignSeoService->editorialPrinciplesPage(),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->editorialPrinciplesPage($breadcrumbs),
        ]);
    }
}
