<?php

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Common\Address;
use AlturaCode\Billing\Core\Common\BillableDetails;
use Carbon\Carbon;
use AlturaCode\Billing\Core\EntitlementChecker;
use AlturaCode\Billing\Core\EntitlementCheckerFactory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

trait HasBilling
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

    public function resolveBillableDetails(): BillableDetails
    {
        return BillableDetails::from(
            displayName: $this->name ?? null,
            email: $this->email ?? null,
            phone: $this->phone ?? null,
            locales: $this->billing_preferred_locales ?? null,
            billingAddress: $this->billing_address ? Address::from(
                line1: $this->billing_address['line_1'] ?? null,
                line2: $this->billing_address['line_2'] ?? null,
                city: $this->billing_address['city'] ?? null,
                stateOrProvince: $this->billing_address['state'] ?? $this->billing_address['province'] ?? null,
                postalCode: $this->billing_address['postal_code'] ?? null,
                countryCode: $this->billing_address['country'] ?? null
            ) : null,
            metadata: $this->billing_metadata ?? [],
        );
    }
}