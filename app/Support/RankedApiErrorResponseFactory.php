<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RankedApiErrorResponseFactory
{
    public function fromValidationException(Request $request, ValidationException $exception): JsonResponse
    {
        $errors = $exception->errors();
        $message = collect($errors)
            ->flatten()
            ->first() ?? 'Nieprawidłowe dane żądania.';

        return match (true) {
            array_key_exists('match_not_started', $errors) => $this->make(
                $request,
                'MATCH_NOT_STARTED',
                (string) $message,
                422,
                $this->matchDetails($request) + [
                    'suggestion' => 'Poczekaj na wspólny countdown i spróbuj ponownie po starcie meczu.',
                ],
                $errors,
            ),
            array_key_exists('match', $errors) => $this->make(
                $request,
                'MATCH_FINISHED',
                (string) $message,
                422,
                $this->matchDetails($request) + [
                    'suggestion' => 'Zaakceptuj wynik meczu i odśwież stan rankingu.',
                ],
                $errors,
            ),
            $this->containsAnswerValidationErrors($errors) => $this->make(
                $request,
                'INVALID_ANSWER',
                (string) $message,
                422,
                $this->matchDetails($request) + [
                    'question_id' => $request->input('question_id'),
                    'suggestion' => 'Wyślij odpowiedź dla pytania należącego do tego meczu.',
                ],
                $errors,
            ),
            default => $this->make(
                $request,
                'VALIDATION_ERROR',
                (string) $message,
                422,
                $this->matchDetails($request) + [
                    'suggestion' => 'Popraw dane wejściowe i ponów żądanie.',
                ],
                $errors,
            ),
        };
    }

    public function matchNotFound(Request $request): JsonResponse
    {
        return $this->make(
            $request,
            'MATCH_NOT_FOUND',
            'Nie znaleziono meczu rankingowego.',
            404,
            $this->matchDetails($request) + [
                'suggestion' => 'Spróbuj ponownie pobrać mecz albo wróć do kolejki.',
            ],
        );
    }

    /**
     * @param  array<int|string, mixed>  $details
     * @param  array<string, array<int, string>>  $validationErrors
     */
    public function make(
        Request $request,
        string $code,
        string $message,
        int $status,
        array $details = [],
        array $validationErrors = [],
    ): JsonResponse {
        $normalizedCode = Str::upper($code);
        $timestamp = now()->utc()->toIso8601String();
        $requestId = $this->requestId($request);

        $payload = [
            'error' => [
                'code' => $normalizedCode,
                'message' => $message,
            ],
            'error_event' => [
                'event' => 'error',
                'id' => sprintf(
                    'evt_error_%s_%s',
                    Str::lower($normalizedCode),
                    Str::uuid()->toString(),
                ),
                'api_version' => '1.0',
                'timestamp' => $timestamp,
                'data' => [
                    'error_code' => $normalizedCode,
                    'message' => $message,
                    'details' => $details,
                ],
            ],
            'meta' => [
                'request_id' => $requestId,
                'server_time' => $timestamp,
                'api_version' => '1.0',
            ],
        ];

        if ($details !== []) {
            $payload['error']['details'] = $details;
        }

        if ($validationErrors !== []) {
            $payload['errors'] = $validationErrors;
        }

        return response()
            ->json($payload, $status)
            ->header('X-Request-Id', $requestId);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    protected function containsAnswerValidationErrors(array $errors): bool
    {
        return array_intersect(array_keys($errors), ['question_id', 'user_answer', 'response_time_ms']) !== [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function matchDetails(Request $request): array
    {
        $rankedMatch = $request->route('rankedMatch');

        return array_filter([
            'match_id' => is_object($rankedMatch) && method_exists($rankedMatch, 'getAttribute')
                ? $rankedMatch->getAttribute('public_id')
                : (is_string($rankedMatch) ? $rankedMatch : null),
            'user_id' => $request->user()?->getKey(),
        ], static fn ($value) => $value !== null);
    }

    protected function requestId(Request $request): ?string
    {
        $requestId = $request->attributes->get('request_id');

        return $requestId !== null ? (string) $requestId : null;
    }
}
