<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscriptionLifecycleEvent
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $tenant;
    public $action;
    public $meta;

    /**
     * Create a new event instance.
     */
    public function __construct($tenant, string $action, array $meta = [])
    {
        $this->tenant = $tenant;
        $this->action = $action;
        $this->meta = $meta;
    }
}
