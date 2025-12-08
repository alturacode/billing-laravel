<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'price_amount' => 'integer',
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
    ];

    public static function fromCore(\AlturaCode\Billing\Core\Subscriptions\SubscriptionItem $subscriptionItem): self
    {
        return new self([
            'price_id' => $subscriptionItem->priceId()->value(),
            'quantity' => $subscriptionItem->quantity(),
            'price_amount' => $subscriptionItem->price()->amount(),
            'price_currency' => $subscriptionItem->price()->currency()->value(),
            'current_period_starts_at' => $subscriptionItem->currentPeriodStartsAt(),
            'current_period_ends_at' => $subscriptionItem->currentPeriodEndsAt(),
        ])->forceFill([
            'id' => $subscriptionItem->id()->value(),
        ]);
    }

    public function toCore(): \AlturaCode\Billing\Core\Subscriptions\SubscriptionItem
    {
        return \AlturaCode\Billing\Core\Subscriptions\SubscriptionItem::hydrate([
            'id' => $this->id,
            'priceId' => $this->price_id,
            'quantity' => $this->quantity,
            'price' => ['amount' => $this->price_amount, 'currency' => $this->price_currency],
            'currentPeriodStartsAt' => $this->current_period_starts_at ? $this->current_period_starts_at->format('Y-m-d H:i:s') : null,
            'currentPeriodEndsAt' => $this->current_period_ends_at ? $this->current_period_ends_at->format('Y-m-d H:i:s') : null,
        ]);
    }
}