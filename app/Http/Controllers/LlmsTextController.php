<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class LlmsTextController extends Controller
{
    public function __invoke(): Response
    {
        return response(File::get(public_path('llms.txt')), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
