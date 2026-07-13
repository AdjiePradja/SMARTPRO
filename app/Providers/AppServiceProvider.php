<?php

namespace App\Providers;

use App\Services\Ai\AiReviewerInterface;
use App\Services\Ai\GeminiReviewer;
use App\Services\Ai\NullReviewer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // AI provider abstraction (D10): swap the binding, not the call sites.
        $this->app->bind(AiReviewerInterface::class, function () {
            if (! config('services.ai.enabled')) {
                return new NullReviewer;
            }

            return match (config('services.ai.provider')) {
                'gemini' => new GeminiReviewer(
                    config('services.gemini.key'),
                    config('services.gemini.model'),
                ),
                'openrouter' => new \App\Services\Ai\OpenRouterReviewer(
                    config('services.openrouter.key'),
                    config('services.openrouter.model'),
                ),
                default => new NullReviewer,
            };
        });
    }

    public function boot(): void
    {
        // Bootstrap 5 pagination markup (matches our Bootstrap-CDN UI).
        Paginator::useBootstrapFive();

        // @can('...') across the app relies on Gate; spatie wires this automatically.
        Blade::if('role', fn (string $role) => auth()->check() && auth()->user()->hasRole($role));
    }
}
