<?php

namespace App\Http\Controllers;

use App\Support\NewsroomHomeCompositionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Throwable;

class AdminNewsroomHomePreviewController extends Controller
{
    public function __invoke(
        Request $request,
        NewsroomHomeCompositionService $compositionService,
    ): Response {
        abort_unless($request->user()?->isAdministrator(), 403);

        try {
            $at = filled($request->query('at'))
                ? Carbon::parse(
                    (string) $request->query('at'),
                    (string) config('app.timezone', 'Europe/Warsaw'),
                )
                : now();
        } catch (Throwable) {
            abort(422, 'Invalid newsroom preview timestamp.');
        }

        $composition = $compositionService->compose($at);
        $previewUrl = route('admin.newsroom.home-preview', [
            'at' => $at->format('Y-m-d H:i:s'),
        ]);

        $response = response()->view('newsroom.home-preview', [
            'composition' => $composition,
            'previewAt' => $at,
            'analyticsEnabled' => false,
            'breadcrumbs' => [],
            'structuredData' => [],
            'meta' => [
                'title' => '[PODGLĄD] Aktualności',
                'description' => 'Prywatny podgląd kompozycji newsroomu.',
                'canonical' => $previewUrl,
                'robots' => 'noindex,nofollow',
                'og_type' => 'website',
            ],
        ]);

        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
