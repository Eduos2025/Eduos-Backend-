<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'invoice_number',
        'amount',
        'status',
        'due_date',
        'paid_at',
        'pdf_path',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}
