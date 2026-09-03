<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LearningHomePayloadBuilder;
use App\Support\PjmFreeAccessDecision;
use App\Support\ProductAccessDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiLearningHomeController extends Controller
{
    public function __invoke(Request $request, LearningHomePayloadBuilder $learningHomePayloadBuilder): JsonResponse
    {
        $validated = $request->validate(LearningHomePayloadBuilder::validationRules());
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $accessDecisions = $learningHomePayloadBuilder->accessDecisions($user);
        $productDecision = $accessDecisions['product'];
        $pjmDecision = $accessDecisions['pjm'];

        if (! $productDecision->allowed && ! $pjmDecision->allowed) {
            return $this->accessRequiredResponse($productDecision, $pjmDecision);
        }

        return response()->json([
            'data' => $learningHomePayloadBuilder->buildForApi(
                $user,
                $validated,
                $productDecision,
                $pjmDecision,
            ),
        ]);
    }

    protected function accessRequiredResponse(
        ProductAccessDecision $productDecision,
        PjmFreeAccessDecision $pjmDecision,
    ): JsonResponse {
        return response()->json([
            'message' => 'Aktywuj dostęp do nauki, żeby kontynuować.',
            'error_code' => 'LEARNING_ACCESS_REQUIRED',
            'data' => [
                'access' => [
                    'full_product' => [
                        'allowed' => false,
                        'reason' => $productDecision->reason,
                        'activation_url' => route('access.activate', absolute: false),
                    ],
                    'pjm' => [
                        'allowed' => false,
                        'reason' => $pjmDecision->reason,
                    ],
                ],
            ],
        ], 403);
    }
}
