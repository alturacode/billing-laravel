<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Subscriptions\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Throwable;

final class Subscription extends Model
{
    use HasUlids;

    protected $guarded = [];
    
    protected $casts = [
        'status' => SubscriptionStatus::class,
        'cancel_at_period_end' => 'boolean',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];
    
    public static function fromCore(\AlturaCode\Billing\Core\Subscriptions\Subscription $subscription): self
    {
        return new self([
            'provider' => $subscription->provider()->value(),
            'billable_id' => $subscription->billable()->id(),
            'billable_type' => $subscription->billable()->type(),
            'name' => $subscription->name()->value(),
            'status' => $subscription->status(),
            'primary_item_id' => $subscription->primaryItem()->id()->value(),
            'cancel_at_period_end' => $subscription->cancelAtPeriodEnd(),
            'trial_ends_at' => $subscription->trialEndsAt(),
            'canceled_at' => $subscription->canceledAt(),
        ])->forceFill([
            'id' => $subscription->id()->value(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public static function saveFromCore(\AlturaCode\Billing\Core\Subscriptions\Subscription $coreSubscription): Subscription
    {
        return DB::transaction(function () use ($coreSubscription) {
            $subscription = self::query()->updateOrCreate([
                'id' => $coreSubscription->id()->value(),
            ], self::fromCore($coreSubscription)->toArray());
            
            $items = [];
            foreach ($coreSubscription->items() as $item) {
                $items[] = [
                    ...SubscriptionItem::fromCore($item)->toArray(),
                    'subscription_id' => $coreSubscription->id()->value(),
                ];
            }
            
            SubscriptionItem::upsert($items, ['id']);
            return $subscription;
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function billable(): BelongsTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function isPaused(): bool
    {
        return $this->status === SubscriptionStatus::Paused;
    }

    public function isCanceled(): bool
    {
        return $this->status === SubscriptionStatus::Canceled;
    }

    public function isIncomplete(): bool
    {
        return $this->status === SubscriptionStatus::Incomplete;
    }

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function scopeName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active);
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Paused);
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Canceled);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Incomplete);
    }

    public function toCore(): \AlturaCode\Billing\Core\Subscriptions\Subscription
    {
        return \AlturaCode\Billing\Core\Subscriptions\Subscription::hydrate([
            'id' => $this->id,
            'billable' => ['id' => $this->billable->id, 'type' => $this->billable->getMorphClass()],
            'provider' => $this->provider,
            'name' => $this->name,
            'status' => $this->status->value,
            'items' => $this->items->map(fn(SubscriptionItem $item) => $item->toCore())->toArray(),
            'primaryItemId' => $this->primary_item_id,
            'createdAt' => $this->created_at->format('Y-m-d H:i:s'),
            'cancelAtPeriodEnd' => $this->cancel_at_period_end,
            'trialEndsAt' => $this->trial_ends_at ? $this->trial_ends_at->format('Y-m-d H:i:s') : null,
            'canceledAt' => $this->canceled_at ? $this->canceled_at->format('Y-m-d H:i:s') : null,
        ]);
    }
}