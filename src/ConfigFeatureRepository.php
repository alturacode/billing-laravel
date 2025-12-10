<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Features\Feature;
use AlturaCode\Billing\Core\Features\FeatureRepository;
use Illuminate\Contracts\Config\Repository;

final readonly class ConfigFeatureRepository implements FeatureRepository
{
    public function __construct(private Repository $config)
    {
    }

    public function all(): array
    {
        $features = $this->config->get('billing.features');
        return array_map(fn(array $feature) => Feature::hydrate($feature), $features ?? []);
    }

    public function find(FeatureKey $key): ?Feature
    {
        return array_find($this->all(), fn(Feature $feature) => $feature->key()->equals($key));
    }
}