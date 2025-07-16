<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Contribution;
use App\Observers\ContributionObserver;

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
        Contribution::observe(ContributionObserver::class);
    }
}
