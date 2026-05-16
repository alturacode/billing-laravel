<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use Illuminate\Database\Eloquent\Model;

final class FeatureUsageEvent extends Model
{
    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'recorded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
