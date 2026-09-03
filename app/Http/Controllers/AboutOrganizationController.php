<?php

namespace App\Http\Controllers;

use App\Support\PublicUrlResolver;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\View\View;

class AboutOrganizationController extends Controller
{
    public function __invoke(
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        PublicUrlResolver $publicUrlResolver,
    ): View {
        $breadcrumbs = $trafficSignBreadcrumbs->organization();
        $organization = (array) config('content.organization');
        $organization['public_url'] = $publicUrlResolver->currentRoot();

        return view('about.organization', [
            'meta' => $trafficSignSeoService->organizationPage(),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $trafficSignSchemaService->organizationPage($breadcrumbs),
            'organization' => $organization,
        ]);
    }
}
