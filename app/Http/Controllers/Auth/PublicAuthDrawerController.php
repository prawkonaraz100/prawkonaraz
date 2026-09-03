<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicAuthDrawerController extends Controller
{
    public function __invoke(Request $request, string $drawer): Response
    {
        abort_unless(in_array($drawer, ['login', 'register'], true), 404);

        return response()
            ->view('auth.drawers.show', ['drawer' => $drawer])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Prawkonaraz-Auth-Drawer', $drawer);
    }
}
