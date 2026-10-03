<?php

namespace App\Http\Controllers;

use App\Support\StudyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AppAuthController extends Controller
{
    public function login(Request $request): Response
    {
        return $this->render($request, 'login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->boolean('session_expired')
                ? 'Twoja sesja wygasła. Zaloguj się ponownie.'
                : session('status'),
        ]);
    }

    public function register(Request $request, StudyContextService $studyContextService): Response
    {
        return $this->render($request, 'register', [
            'categories' => $studyContextService->activeCategories()
                ->map(fn ($category) => [
                    'id' => $category->getKey(),
                    'code' => $category->code,
                    'name' => $category->name,
                    'short_name' => $studyContextService->shortCategoryName($category),
                ])
                ->values(),
        ]);
    }

    private function render(Request $request, string $mode, array $props): Response
    {
        $response = Inertia::render('App/AuthFullScreen', [
            'mode' => $mode,
            ...$props,
        ])->toResponse($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
