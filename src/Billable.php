<?php

namespace AlturaCode\Billing\Laravel;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

trait Billable
{
    public function subscription(string $name = 'default'): ?Subscription
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->subscriptions->first(fn($subscription) => $subscription->name === $name);
    }

    public function subscriptions()
    {
        /** @var Model $this */
        return $this->morphMany(Config::get('billing.models.subscription'), 'billable')
            ->orderBy('created_at', 'desc');
    }

    public function subscribed(string $name = 'default'): bool
    {
        $subscription = $this->subscription($name);
        return $subscription && $subscription->isActive();
    }

    /**
     * @throws BindingResolutionException
     */
    public function newSubscription(string $name)
    {
        /** @var $this Model */
        return App::make(SubscriptionBuilder::class)
            ->withName($name)
            ->withProvider(Config::get('billing.provider'))
            ->withBillable($this->getMorphClass(), $this->getKey());
    }
}