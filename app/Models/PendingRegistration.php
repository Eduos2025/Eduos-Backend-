<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingRegistration extends Model
{
    protected $fillable = [
        'reference',
        'plan_id',
        'billing_interval',
        'subdomain',
        'school_name',
        'school_email',
        'owner_name',
        'owner_email',
        'owner_password',
        'amount',
        'payment_method',
        'coupon_id',
        'status',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
