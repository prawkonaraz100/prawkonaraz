<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactMessageMail;
use App\Support\PublicUrlResolver;
use App\Support\TrafficSignBreadcrumbs;
use App\Support\TrafficSignSchemaService;
use App\Support\TrafficSignSeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class ContactPageController extends Controller
{
    public function __invoke(
        TrafficSignSeoService $trafficSignSeoService,
        TrafficSignSchemaService $trafficSignSchemaService,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        PublicUrlResolver $publicUrlResolver,
    ): View {
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

    public function store(ContactMessageRequest $request): RedirectResponse
    {
        $messageData = [
            'name' => (string) $request->validated('contact_name'),
            'email' => (string) $request->validated('contact_email'),
            'topic' => $request->topicLabel(),
            'message' => (string) $request->validated('contact_message'),
        ];

        try {
            Mail::to((string) config('content.organization.email'))
                ->send(new ContactMessageMail($messageData));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('home')
                ->withFragment('kontakt')
                ->withInput($request->safe()->except(['contact_consent', 'website']))
                ->with('contact_error', 'Nie udało się teraz wysłać wiadomości. Spróbuj ponownie za chwilę.');
        }

        return redirect()
            ->route('home')
            ->withFragment('kontakt')
            ->with('contact_success', 'Dziękujemy! Wiadomość została wysłana do naszego zespołu.');
    }
}
