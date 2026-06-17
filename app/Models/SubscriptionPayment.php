<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id',
        'invoice_id',
        'amount',
        'gateway',
        'reference',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'float',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
