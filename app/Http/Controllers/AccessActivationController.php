<?php

namespace App\Http\Controllers;

use App\Support\ProductAccessResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessActivationController extends Controller
{
    public function __invoke(Request $request, ProductAccessResolver $productAccessResolver): Response|RedirectResponse
    {
        $decision = $productAccessResolver->forUser($request->user());

        if ($decision->allowed) {
            return to_route('session.index');
        }

        return Inertia::render('Access/Activate', [
            'access' => [
                'allowed' => $decision->allowed,
                'reason' => $decision->reason,
                'source' => $decision->source,
            ],
            'pricingUrl' => route('public.pricing', absolute: false),
            'contactUrl' => route('about.contact', absolute: false),
        ]);
    }
}
