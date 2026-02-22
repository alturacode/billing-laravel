<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FeatureUsage extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'window_end' => 'datetime',
            'used' => 'integer',
        ];
    }
}