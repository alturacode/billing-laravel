<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SubscriptionItem extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'price_amount' => 'integer',
        'interval_type' => 'string',
        'interval_count' => 'integer',
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
    ];

    public function entitlements(): HasMany
    {
        return $this->hasMany(SubscriptionItemEntitlement::class, 'subscription_item_id');
    }

    public static function fromCore(\AlturaCode\Billing\Core\Subscriptions\SubscriptionItem $subscriptionItem): self
    {
        return new self([
            'id' => $subscriptionItem->id()->value(),
            'price_id' => $subscriptionItem->priceId()->value(),
            'quantity' => $subscriptionItem->quantity(),
            'price_amount' => $subscriptionItem->price()->amount(),
            'price_currency' => $subscriptionItem->price()->currency()->code(),
            'interval_type' => $subscriptionItem->interval()->type(),
            'interval_count' => $subscriptionItem->interval()->count(),
            'current_period_starts_at' => $subscriptionItem->currentPeriodStartsAt(),
            'current_period_ends_at' => $subscriptionItem->currentPeriodEndsAt(),
        ]);
    }

    public function toCore(): \AlturaCode\Billing\Core\Subscriptions\SubscriptionItem
    {
        return \AlturaCode\Billing\Core\Subscriptions\SubscriptionItem::hydrate($this->toCoreArray());
    }

    public function toCoreArray(): array
    {
        return [
            'id' => $this->id,
            'price_id' => $this->price_id,
            'quantity' => $this->quantity,
            'price' => ['amount' => $this->price_amount, 'currency' => $this->price_currency],
            'interval' => ['type' => $this->interval_type, 'count' => $this->interval_count],
            'entitlements' => $this->entitlements->map(fn(SubscriptionItemEntitlement $entitlement) => $entitlement->toCoreArray())->toArray(),
            'current_period_starts_at' => $this->current_period_starts_at ? $this->current_period_starts_at->format('Y-m-d H:i:s') : null,
            'current_period_ends_at' => $this->current_period_ends_at ? $this->current_period_ends_at->format('Y-m-d H:i:s') : null,
        ];
    }
}