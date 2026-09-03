<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureModeratorPanelAccess;
use App\Http\Middleware\EnsurePjmModuleAccess;
use App\Http\Middleware\EnsureProductAccess;
use App\Http\Middleware\EnsureStudySessionAccess;
use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\MarkReturningUser;
use App\Http\Middleware\TrackUserIpActivity;
use App\Support\ApiErrorResponseFactory;
use App\Support\RankedApiErrorResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->append(AssignRequestId::class);

        $middleware->web(append: [
            EnsureUserIsNotBanned::class,
            TrackUserIpActivity::class,
            MarkReturningUser::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'moderator.panel' => EnsureModeratorPanelAccess::class,
            'pjm.access' => EnsurePjmModuleAccess::class,
            'product.access' => EnsureProductAccess::class,
            'study.session.access' => EnsureStudySessionAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $apiError = static function (
            Request $request,
            string $code,
            string $message,
            int $status,
            array $details = [],
        ): Response {
            return app(ApiErrorResponseFactory::class)->make(
                $request,
                $code,
                $message,
                $status,
                $details,
            );
        };

        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->context(function (): array {
            $request = app()->bound('request') ? request() : null;

            return [
                'request_id' => $request?->attributes->get('request_id'),
                'method' => $request?->method(),
                'path' => $request?->path(),
                'user_id' => $request?->user()?->getKey(),
            ];
        });

        $exceptions->respond(function (Response $response): Response {
            $requestId = request()?->attributes->get('request_id');

            if ($requestId !== null && ! $response->headers->has('X-Request-Id')) {
                $response->headers->set('X-Request-Id', (string) $requestId);
            }

            return $response;
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($apiError) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError(
                $request,
                'UNAUTHORIZED',
                'Wymagane jest logowanie.',
                401,
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($apiError) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError(
                $request,
                'FORBIDDEN',
                'Brak uprawnien do wykonania tej operacji.',
                403,
            );
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($apiError) {
            if ($request->is('api/v1/ranked/matches/*')) {
                return app(RankedApiErrorResponseFactory::class)->matchNotFound($request);
            }

            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError(
                $request,
                'NOT_FOUND',
                'Nie znaleziono zasobu.',
                404,
            );
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/v1/ranked/*')) {
                return null;
            }

            return app(RankedApiErrorResponseFactory::class)
                ->fromValidationException($request, $exception);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($apiError) {
            if ($request->is('api/v1/ranked/matches/*')) {
                return app(RankedApiErrorResponseFactory::class)->matchNotFound($request);
            }

            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError(
                $request,
                'NOT_FOUND',
                'Nie znaleziono zasobu.',
                404,
            );
        });

        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) use ($apiError) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError(
                $request,
                'METHOD_NOT_ALLOWED',
                'Ta operacja nie jest dostepna dla wskazanej trasy.',
                405,
            );
        });

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) use ($apiError) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError(
                $request,
                'RATE_LIMITED',
                'Przekroczono limit zapytan. Sprobuj ponownie za chwile.',
                429,
            );
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($apiError) {
            if (
                $exception->getStatusCode() !== 419
                || ! $exception->getPrevious() instanceof TokenMismatchException
            ) {
                return null;
            }

            logger()->warning('csrf_token_mismatch', [
                'request_id' => $request->attributes->get('request_id'),
                'method' => $request->method(),
                'path' => $request->path(),
                'route_name' => $request->route()?->getName(),
                'user_id' => $request->user()?->getKey(),
                'has_session_cookie' => $request->cookies->has((string) config('session.cookie')),
                'has_form_token' => $request->request->has('_token'),
                'has_csrf_header' => $request->headers->has('X-CSRF-TOKEN'),
                'has_xsrf_header' => $request->headers->has('X-XSRF-TOKEN'),
            ]);

            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return $apiError(
                $request,
                'CSRF_TOKEN_MISMATCH',
                'Sesja wygasła. Odśwież stronę lub zaloguj się ponownie.',
                419,
            );
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($apiError) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match ($exception->getStatusCode()) {
                403 => $apiError(
                    $request,
                    'FORBIDDEN',
                    'Brak uprawnien do wykonania tej operacji.',
                    403,
                ),
                409 => $apiError(
                    $request,
                    'CONFLICT',
                    'Operacja nie moze zostac wykonana w obecnym stanie zasobu.',
                    409,
                ),
                default => null,
            };
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($apiError) {
            if (
                ! $request->is('api/*')
                || $exception instanceof ValidationException
                || $exception instanceof HttpExceptionInterface
            ) {
                return null;
            }

            return $apiError(
                $request,
                'INTERNAL_ERROR',
                'Wewnetrzny blad serwera.',
                500,
            );
        });
    })->create();
