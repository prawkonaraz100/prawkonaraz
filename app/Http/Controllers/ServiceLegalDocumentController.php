<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ServiceLegalDocumentController extends Controller
{
    public function terms(): View
    {
        return $this->renderDocument('terms');
    }

    public function privacy(): View
    {
        return $this->renderDocument('privacy');
    }

    private function renderDocument(string $document): View
    {
        $organization = (array) config('content.organization');
        $legalDocuments = (array) config('content.legal_documents');
        $operatorComplete = filled($organization['legal_name'] ?? null)
            && filled($legalDocuments['operator_address'] ?? null);
        $isTerms = $document === 'terms';
        $routeName = $isTerms ? 'legal.terms' : 'legal.privacy';
        $title = $isTerms ? 'Regulamin serwisu' : 'Polityka prywatności';

        return view('service-legal.'.$document, [
            'meta' => [
                'title' => $title.' | prawkonaraz.pl',
                'description' => $isTerms
                    ? 'Zasady korzystania z serwisu prawkonaraz.pl, konta kursanta, materiałów edukacyjnych i dostępu do nauki.'
                    : 'Informacje o przetwarzaniu danych osobowych, logowaniu przez Google, plikach cookie i prawach użytkownika w prawkonaraz.pl.',
                'canonical' => route($routeName),
                'robots' => $operatorComplete ? 'index,follow' : 'noindex,follow',
            ],
            'breadcrumbs' => [
                ['label' => 'Strona główna', 'url' => route('home')],
                ['label' => $title, 'url' => route($routeName)],
            ],
            'organization' => $organization,
            'legalDocuments' => $legalDocuments,
            'operatorComplete' => $operatorComplete,
        ]);
    }
}
