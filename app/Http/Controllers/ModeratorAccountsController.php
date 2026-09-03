<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModeratorAccountRequest;
use App\Models\User;
use App\Support\ModeratorAccountProvisioningService;
use App\Support\ModeratorAccountsPanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModeratorAccountsController extends Controller
{
    public function index(Request $request, ModeratorAccountsPanelService $panelService): Response
    {
        return Inertia::render(
            'Moderator/Accounts/Index',
            $panelService->dataFor(
                $request->user(),
                $request->session()->get('moderator_created_account'),
            ),
        );
    }

    public function store(
        StoreModeratorAccountRequest $request,
        ModeratorAccountProvisioningService $provisioningService,
    ): RedirectResponse {
        $createdAccount = $provisioningService->create($request->user(), $request->validated());

        return to_route('moderator.accounts.index')
            ->with('moderator_created_account', $createdAccount)
            ->with('status', 'Konto kursanta zostało utworzone.');
    }

    public function regenerateStartPassword(
        Request $request,
        User $account,
        ModeratorAccountProvisioningService $provisioningService,
    ): RedirectResponse {
        $createdAccount = $provisioningService->regenerateStartPassword($request->user(), $account);

        return to_route('moderator.accounts.index')
            ->with('moderator_created_account', $createdAccount)
            ->with('status', 'Wygenerowano nowe hasło startowe.');
    }
}
