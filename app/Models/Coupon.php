<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'value',
        'max_uses',
        'used_count',
        'plan_restrictions',
        'active',
        'expires_at',
    ];

    protected $casts = [
        'plan_restrictions' => 'array',
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'value' => 'float',
    ];

    public function isValidForPlan(Plan $plan): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->plan_restrictions && !in_array($plan->slug, $this->plan_restrictions)) {
            return false;
        }

        return true;
    }
}
