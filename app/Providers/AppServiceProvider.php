<?php

namespace App\Providers;

use App\Events\ContentArticlePublicReadChanged;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Events\ContentHomePlacementChanged;
use App\Listeners\InvalidateNewsroomHomeCacheOnPlacementChange;
use App\Listeners\InvalidateNewsroomReadCacheOnArticleWorkflowTransition;
use App\Listeners\InvalidateNewsroomReadCacheOnPublicArticleChange;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use App\Models\QuestionPublicExplanation;
use App\Observers\ContentAuthorObserver;
use App\Observers\ContentCategoryObserver;
use App\Observers\ContentTopicObserver;
use App\Observers\QuestionPublicExplanationObserver;
use App\Support\SharedAuthorTrafficSignSchemaService;
use App\Support\TrafficSignSchemaService;
use App\Support\VerificationEmailMessage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TrafficSignSchemaService::class, SharedAuthorTrafficSignSchemaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(fn ($user, string $url) => app(VerificationEmailMessage::class)->build($url));

        ContentAuthor::observe(ContentAuthorObserver::class);
        ContentCategory::observe(ContentCategoryObserver::class);
        ContentTopic::observe(ContentTopicObserver::class);
        QuestionPublicExplanation::observe(QuestionPublicExplanationObserver::class);

        Event::listen(
            ContentArticleWorkflowTransitioned::class,
            InvalidateNewsroomReadCacheOnArticleWorkflowTransition::class,
        );
        Event::listen(
            ContentArticlePublicReadChanged::class,
            InvalidateNewsroomReadCacheOnPublicArticleChange::class,
        );
        Event::listen(
            ContentHomePlacementChanged::class,
            InvalidateNewsroomHomeCacheOnPlacementChange::class,
        );

        RateLimiter::for('contact', function (Request $request): Limit {
            $identity = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(5)->by('contact:'.$identity);
        });

        $rateLimitResponse = static function (Request $request, array $headers) {
            $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));
            $message = "Zbyt wiele prób. Spróbuj ponownie za {$seconds} sekund.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 429, $headers);
            }

            return back()->withErrors(['email' => $message])
                ->withInput($request->except('password', 'password_confirmation'))
                ->withHeaders($headers);
        };

        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)
            ->by('registration:'.$request->ip())->response($rateLimitResponse));
        RateLimiter::for('verification-email', fn (Request $request) => Limit::perMinute(6)
            ->by('verification-email:'.$request->user()->getAuthIdentifier())->response($rateLimitResponse));
    }
}
