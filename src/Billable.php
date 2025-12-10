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

    public function subscribed(string $name = 'default'): bool
    {
        $subscription = $this->subscription($name);
        return $subscription && $subscription->isActive();
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