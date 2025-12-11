<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Features\FeatureRepository;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Provider\ExternalIdMapper;
use AlturaCode\Billing\Core\Subscriptions\SubscriptionRepository;
use Illuminate\Support\ServiceProvider;

final class BillingLaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerBillingProviderRegistry();
        $this->registerRepositories();

        $this->app->bind(ExternalIdMapper::class, DatabaseExternalIdMapperStorage::class);

        $this->mergeConfigFrom(__DIR__ . '/../config/billing.php', 'billing');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/billing.php' => $this->app->configPath('billing.php'),
        ], 'alturacode-billing-config');

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
        ], 'alturacode-billing-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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
        $featureRepository = $this->app['config']->get('billing.repositories.features', ConfigFeatureRepository::class);
        $this->app->bind(FeatureRepository::class, $featureRepository);

        $productRepository = $this->app['config']->get('billing.repositories.products', ConfigProductRepository::class);
        $this->app->bind(ProductRepository::class, $productRepository);

        $subscriptionRepository = $this->app['config']->get('billing.repositories.subscriptions', EloquentSubscriptionRepository::class);
        $this->app->bind(SubscriptionRepository::class, $subscriptionRepository);
    }
}