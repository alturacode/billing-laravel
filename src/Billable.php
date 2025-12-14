<?php

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Common\BillableDetails;
use AlturaCode\Billing\Core\EntitlementChecker;
use Carbon\Carbon;

interface Billable
{
    public function subscription(string $name = 'default'): ?Subscription;
    public function subscriptions();
    public function subscribed(string $name = 'default'): bool;
    public function features(string $name = 'default', ?Carbon $date = null): EntitlementChecker;
    public function newSubscription(string $name = 'default'): SubscriptionBuilder;
    public function resolveBillableDetails(): BillableDetails;
}