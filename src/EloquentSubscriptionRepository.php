<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Subscriptions\Subscription;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionItemId;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionName;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use AlturaCode\Billing\Laravel\Subscription as EloquentSubscription;
use Throwable;

final readonly class EloquentSubscriptionRepository implements SubscriptionRepository
{
    public function find(SubscriptionId $subscriptionId): ?Subscription
    {
        return EloquentSubscription::find($subscriptionId->value())->toCore();
    }

    /**
     * @throws Throwable
     */
    public function save(Subscription $subscription): void
    {
        EloquentSubscription::saveFromCore($subscription);
    }

    public function findForBillable(BillableIdentity $billable, SubscriptionName $subscriptionName): ?Subscription
    {
        return EloquentSubscription::query()
            ->with('items')
            ->name($subscriptionName->value())
            ->where('billable_id', $billable->id())
            ->where('billable_type', $billable->type())
            ->first()?->toCore();
    }

    public function findAllForBillable(BillableIdentity $billable): array
    {
        return EloquentSubscription::query()
            ->with('items')
            ->where('billable_id', $billable->id())
            ->where('billable_type', $billable->type())
            ->get()
            ->map(fn(EloquentSubscription $subscription) => $subscription->toCore())
            ->toArray();
    }

    public function findByItemId(SubscriptionItemId $itemId): ?Subscription
    {
        return EloquentSubscription::query()
            ->with('items')
            ->whereHas('items', fn($query) => $query->where('id', $itemId->value()))
            ->first()?->toCore();
    }
}