<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Provider\BillingProvider;
use InvalidArgumentException;

final readonly class BillingProviderRegistry implements \AlturaCode\Billing\Core\Provider\BillingProviderRegistry
{
    public function __construct(private array $providers)
    {
    }

    public function subscriptionProviderFor(string $provider): BillingProvider
    {
        return $this->providers[$provider] ?? throw new InvalidArgumentException("Provider [$provider] is not registered.");
    }
}