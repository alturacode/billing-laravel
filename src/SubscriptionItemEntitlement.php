<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SubscriptionItemEntitlement extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'feature_key' => 'string',
        'feature_kind' => 'string',
        'feature_value_string' => 'string',
        'feature_value_integer' => 'integer',
        'feature_value_boolean' => 'boolean',
        'effective_window_starts_at' => 'datetime',
        'effective_window_ends_at' => 'datetime',
    ];

    public static function fromCore(\AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement $entitlement): self
    {
        $featureValue = $entitlement->value()->value();
        $featureValueIsString = is_string($featureValue);
        $featureValueIsInteger = is_int($featureValue);
        $featureValueIsBoolean = is_bool($featureValue);
        return new self([
            'id' => $entitlement->id()->value(),
            'feature_key' => $entitlement->key()->value(),
            'feature_value_kind' => $entitlement->value()->kind()->value,
            'feature_value_string' => $featureValueIsString ? $featureValue : null,
            'feature_value_integer' => $featureValueIsInteger ? $featureValue : null,
            'feature_value_boolean' => $featureValueIsBoolean ? $featureValue : null,
            'effective_window_starts_at' => $entitlement->effectiveWindow()?->startsAt(),
            'effective_window_ends_at' => $entitlement->effectiveWindow()?->endsAt(),
        ]);
    }

    public function toCore(): \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement
    {
        return \AlturaCode\Billing\Core\Subscriptions\SubscriptionItemEntitlement::hydrate($this->toCoreArray());
    }

    public function toCoreArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->feature_key,
            'value' => [
                'kind' => $this->feature_value_kind,
                'value' => $this->feature_value_boolean ?? $this->feature_value_integer ?? $this->feature_value_string,
            ],
            'effectiveWindow' => $this->effective_window_starts_at || $this->effective_window_ends_at ? [
                'start' => $this->effective_window_starts_at->format('Y-m-d H:i:s'),
                'end' => $this->effective_window_ends_at->format('Y-m-d H:i:s'),
            ] : null
        ];
    }
}