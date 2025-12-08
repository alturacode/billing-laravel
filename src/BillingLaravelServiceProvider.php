<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Features\FeatureRepository;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use Illuminate\Support\ServiceProvider;

final class BillingLaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerBillingProviderRegistry();
        $this->registerRepositories();
    }

    public function boot()
    {
    }

    private function registerBillingProviderRegistry(): void
    {
        $this->app->singleton(\AlturaCode\Billing\Core\Provider\BillingProviderRegistry::class, function ($app) {
            return new BillingProviderRegistry(array_map(function ($provider) use ($app) {
                return $app->make($provider);
            }, $app['config']->get('billing.providers')));
        });
    }

    private function registerRepositories(): void
    {
        $featureRepository = $this->app['config']->get('billing.repositories.features');
        $this->app->singleton(FeatureRepository::class, $featureRepository);

        $productRepository = $this->app['config']->get('billing.repositories.plans');
        $this->app->singleton(ProductRepository::class, $productRepository);

        $subscriptionRepository = $this->app['config']->get('billing.repositories.subscriptions');
        $this->app->singleton(SubscriptionRepository::class, $subscriptionRepository);
    }
}