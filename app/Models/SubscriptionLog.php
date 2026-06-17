<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'action',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function log(?string $tenantId, string $action, string $description, ?array $meta = null): void
    {
        self::create([
            'tenant_id' => $tenantId,
            'action' => $action,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
