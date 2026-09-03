<?php

namespace App\Http\Controllers;

use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\View\View;

class HowItWorksPageController extends Controller
{
    public function __invoke(
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
    ): View {
        $breadcrumbs = $trafficSignBreadcrumbs->howItWorks();

        return view('about.how-it-works', [
            'meta' => $trafficSignSeoService->howItWorksPage(),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->howItWorksPage($breadcrumbs),
        ]);
    }
}
