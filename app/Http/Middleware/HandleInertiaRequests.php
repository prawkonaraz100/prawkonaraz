<?php

namespace App\Http\Middleware;

use App\Support\GoogleIdentityRegistrationSession;
use App\Support\PaymentRequirementService;
use App\Support\PublicFooter;
use App\Support\PublicNavigation;
use App\Support\StudyContextService;
use App\Support\UserAvatarService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $studyContext = app(StudyContextService::class);
        $navigation = app(PublicNavigation::class);
        $footer = app(PublicFooter::class);
        $user = $request->user();
        $userAvatar = $user ? app(UserAvatarService::class)->payload($user) : null;
        $navigationData = $navigation->data($user);
        $registrationCategories = $user ? [] : fn () => $studyContext->activeCategories()
            ->map(fn ($category) => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'short_name' => $studyContext->shortCategoryName($category),
            ])
            ->values()
            ->all();

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'request_id' => $request->attributes->get('request_id'),
            ],
            'auth' => [
                'user' => $user ? [
                    ...$user->only([
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'is_admin',
                    ]),
                    'avatar_url' => $userAvatar['url'],
                    'avatar_initials' => $userAvatar['initials'],
                    'has_uploaded_avatar' => $userAvatar['has_uploaded_avatar'],
                ] : null,
            ],
            'authDrawers' => [
                'registrationCategories' => $registrationCategories,
                'enabledSocialProviders' => $this->enabledSocialProviders(),
                'googleIdentityRegistration' => app(GoogleIdentityRegistrationSession::class)->payload($request),
                'paymentRequired' => app(PaymentRequirementService::class)->requiresPayment(),
            ],
            'googleIdentity' => $this->googleIdentityConfig(),
            'navigation' => $navigationData,
            'footer' => $footer->data($user, $navigationData),
            'studyContext' => $user ? [
                'targetCategoryId' => $studyContext->preferredCategoryId($user),
                'visualExplanationsEnabled' => $studyContext->visualExplanationsEnabled($user),
                'visualExplanationsMode' => $studyContext->visualExplanationsMode($user),
                'canSwitchCategory' => $studyContext->canChangeTargetCategory($user),
                'categoryLocked' => ! $studyContext->canChangeTargetCategory($user),
                'categories' => $studyContext->activeCategories($user)
                    ->map(fn ($category) => [
                        'id' => $category->getKey(),
                        'code' => $category->code,
                        'name' => $category->name,
                        'short_name' => $studyContext->shortCategoryName($category),
                    ])
                    ->values()
                    ->all(),
            ] : [
                'targetCategoryId' => null,
                'visualExplanationsEnabled' => true,
                'visualExplanationsMode' => 'after_incorrect',
                'canSwitchCategory' => false,
                'categoryLocked' => false,
                'categories' => [],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function enabledSocialProviders(): array
    {
        return collect(['google', 'facebook'])
            ->filter(fn (string $provider): bool => filled(config("services.$provider.client_id"))
                && filled(config("services.$provider.client_secret")))
            ->values()
            ->all();
    }

    /**
     * @return array{enabled: bool, oneTapEnabled: bool, clientId: string|null, loginUrl: string}
     */
    private function googleIdentityConfig(): array
    {
        $clientId = config('services.google.client_id');
        $enabled = (bool) config('services.google.identity_enabled')
            && filled($clientId);

        return [
            'enabled' => $enabled,
            'oneTapEnabled' => $enabled && (bool) config('services.google.one_tap_enabled'),
            'clientId' => $enabled ? (string) $clientId : null,
            'loginUrl' => route('google.identity.login', absolute: false),
        ];
    }
}
