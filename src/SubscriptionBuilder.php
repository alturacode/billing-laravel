<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\BillingManager;
use AlturaCode\Billing\Core\SubscriptionDraftBuilder;

/** @mixin SubscriptionDraftBuilder */
final readonly class SubscriptionBuilder
{
    public function __construct(
        private SubscriptionDraftBuilder $draftBuilder,
        private BillingManager           $manager
    )
    {
    }

    public function __call(string $name, array $arguments): SubscriptionDraftBuilder
    {
        return $this->draftBuilder->{$name}(...$arguments);
    }

    public function create(array $providerOptions = []): BillingProviderResult
    {
        return new BillingProviderResult($this->manager->createSubscription($this->draftBuilder->build(), $providerOptions));
    }
}