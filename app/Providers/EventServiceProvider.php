<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ContributionCreated;
use App\Events\ContributionMade;
use App\Events\WithdrawalCompleted;
use App\Listeners\UpdateCampaignAmount;
use App\Listeners\SendContributionNotification;
use App\Listeners\SendWithdrawalNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ContributionCreated::class => [
            UpdateCampaignAmount::class,
        ],
        ContributionMade::class => [
            SendContributionNotification::class,
        ],
        WithdrawalCompleted::class => [
            SendWithdrawalNotification::class,
        ],
    ];
    
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
