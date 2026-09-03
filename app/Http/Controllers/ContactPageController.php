<?php

namespace App\Http\Controllers;

use App\Support\PublicUrlResolver;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\View\View;

class ContactPageController extends Controller
{
    public function __invoke(
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        PublicUrlResolver $publicUrlResolver,
    ): View
    {
        $breadcrumbs = $trafficSignBreadcrumbs->contact();
        $organization = (array) config('content.organization');
        $organization['public_url'] = $publicUrlResolver->currentRoot();

        return view('about.contact', [
            'meta' => $trafficSignSeoService->contactPage(),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->contactPage($breadcrumbs),
            'organization' => $organization,
        ]);
    }
}
