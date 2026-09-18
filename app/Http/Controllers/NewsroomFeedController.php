<?php

namespace App\Http\Controllers;

use App\Support\NewsroomFeedService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class NewsroomFeedController extends Controller
{
    public function __invoke(Request $request, NewsroomFeedService $feedService): Response
    {
        $feed = $feedService->build();

        if ($feed === null) {
            abort(404);
        }

        $xml = $feedService->render($feed);
        $response = response($xml, Response::HTTP_OK);
        $lastModified = Carbon::parse($feed['updated'])->utc();

        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setEtag(hash('sha256', $xml));
        $response->setLastModified($lastModified);
        $response->setPublic();
        $response->setMaxAge(max(1, (int) config('newsroom.cache_ttl_seconds', 60)));

        if ($response->isNotModified($request)) {
            $response->setLastModified($lastModified);
        }

        return $response;
    }
}
