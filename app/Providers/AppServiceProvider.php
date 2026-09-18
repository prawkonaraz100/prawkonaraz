<?php

namespace App\Providers;

use App\Events\ContentArticlePublicReadChanged;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Events\ContentHomePlacementChanged;
use App\Listeners\InvalidateNewsroomHomeCacheOnPlacementChange;
use App\Listeners\InvalidateNewsroomReadCacheOnArticleWorkflowTransition;
use App\Listeners\InvalidateNewsroomReadCacheOnPublicArticleChange;
use App\Models\ContentCategory;
use App\Models\QuestionPublicExplanation;
use App\Observers\ContentCategoryObserver;
use App\Observers\QuestionPublicExplanationObserver;
use App\Support\SharedAuthorTrafficSignSchemaService;
use App\Support\TrafficSignSchemaService;
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
        ContentCategory::observe(ContentCategoryObserver::class);
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
    }
}
