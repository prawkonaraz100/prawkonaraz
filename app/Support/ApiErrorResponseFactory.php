<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiErrorResponseFactory
{
    /**
     * @param  array<int|string, mixed>  $details
     */
    public function make(
        Request $request,
        string $code,
        string $message,
        int $status,
        array $details = [],
    ): JsonResponse {
        $payload = [
            'error' => [
                'code' => strtoupper($code),
                'message' => $message,
            ],
            'meta' => [
                'request_id' => $this->requestId($request),
                'server_time' => now()->utc()->toIso8601String(),
            ],
        ];

        if ($details !== []) {
            $payload['error']['details'] = $details;
        }

        return response()
            ->json($payload, $status)
            ->header('X-Request-Id', $this->requestId($request));
    }

    protected function requestId(Request $request): ?string
    {
        $requestId = $request->attributes->get('request_id');

        return $requestId !== null ? (string) $requestId : null;
    }
}
