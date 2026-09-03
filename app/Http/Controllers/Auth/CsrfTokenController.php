<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsrfTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()
            ->json([
                'token' => $request->session()->token(),
                'authenticated' => $request->user() !== null,
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}
