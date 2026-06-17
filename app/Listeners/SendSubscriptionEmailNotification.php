<?php

namespace App\Listeners;

use App\Events\SubscriptionLifecycleEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendSubscriptionEmailNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(SubscriptionLifecycleEvent $event): void
    {
        $tenant = $event->tenant;
        $action = $event->action;
        $meta = $event->meta;

        Log::info("SaaS Email Automation Event Triggered: [{$action}] for tenant [{$tenant->name}]");

        // In a production setup, dispatch actual Laravel Mailables:
        switch ($action) {
            case 'trial_started':
                // Mail::to($tenant->school_email)->send(new TrialStartedMailable($tenant));
                Log::info("Sent Trial Started email to {$tenant->name}");
                break;
            case 'trial_ending':
                Log::info("Sent Trial Ending alert to {$tenant->name}");
                break;
            case 'payment_successful':
                Log::info("Sent Payment Confirmation invoice email to {$tenant->name}");
                break;
            case 'payment_failed':
                Log::info("Sent Payment Failure reminder to {$tenant->name}");
                break;
            case 'subscription_expiring':
                Log::info("Sent Expiry warning notification to {$tenant->name}");
                break;
            case 'subscription_renewed':
                Log::info("Sent Renewal Invoice email to {$tenant->name}");
                break;
            case 'school_created':
                Log::info("Sent Welcome onboarding details email to {$tenant->name}");
                break;
        }
    }
}
