<?php

namespace App\Http\Controllers;

use App\Support\LegalContentCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLegalUnitSearchController extends Controller
{
    public function index(Request $request, LegalContentCatalogService $legalContentCatalogService): JsonResponse
    {
        abort_unless((bool) $request->user()?->isAdministrator(), 403);

        $query = trim((string) $request->string('q')->value());
        $limit = $request->integer('limit', 20);

        return response()->json([
            'data' => [
                'legal_units' => $legalContentCatalogService->searchLegalUnitEditorOptions($query, $limit),
            ],
        ]);
    }
}
