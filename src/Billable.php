<?php

namespace AlturaCode\Billing\Laravel;

use Carbon\Carbon;
use AlturaCode\Billing\Core\EntitlementChecker;
use AlturaCode\Billing\Core\EntitlementCheckerFactory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

trait Billable
{
    public function subscription(string $name = 'default'): ?Subscription
    {
        return $this->subscriptions->first(fn($subscription) => $subscription->name === $name);
    }

    public function subscriptions()
    {
        return $this->morphMany(Config::get('billing.models.subscription'), 'billable')
            ->orderBy('created_at', 'desc');
    }

    public function subscribed(string $name = 'default', ?string $plan = null): bool
    {
        // @todo we should introduce a checker in core

        $subscription = $this->subscription($name);

        if ($subscription === null) {
            return false;
        }

        if ($plan !== null) {
            // @todo we should check by slug as well
            return $subscription->primary_item_id === $plan;
        }

        return $subscription->isActive();
    }

    public function features(string $name = 'default', ?Carbon $date = null): EntitlementChecker
    {
        return App::make(EntitlementCheckerFactory::class)->create(
            $this->subscription($name)->toCore(), ($date ?? now())->toDateTimeImmutable()
        );
    }

    public function newSubscription(string $name = 'default'): SubscriptionBuilder
    {
        return App::make(SubscriptionBuilder::class)
            ->withName($name)
            ->withProvider(Config::get('billing.provider'))
            ->withBillable($this->getMorphClass(), $this->getKey());
    }
}