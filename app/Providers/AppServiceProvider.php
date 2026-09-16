<?php

namespace App\Providers;

use App\Models\QuestionPublicExplanation;
use App\Observers\QuestionPublicExplanationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        QuestionPublicExplanation::observe(QuestionPublicExplanationObserver::class);

        RateLimiter::for('contact', function (Request $request): Limit {
            $identity = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(5)->by('contact:'.$identity);
        });
    }
}
