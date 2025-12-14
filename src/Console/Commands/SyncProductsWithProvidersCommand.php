<?php

namespace AlturaCode\Billing\Laravel\Console\Commands;

use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Provider\ProductAwareBillingProvider;
use AlturaCode\Billing\Laravel\BillingProviderRegistry;
use Illuminate\Console\Command;

class SyncProductsWithProvidersCommand extends Command
{
    protected $signature = 'billing:sync-products';

    protected $description = 'Sync product definitions with product-aware billing providers.';

    public function handle(BillingProviderRegistry $registry, ProductRepository $productRepository): void
    {
        /** @var ProductAwareBillingProvider[] $providers */
        $providers = $registry->productAwareProviders();
        $products = $productRepository->all();

        if (empty($providers)) {
            $this->info('No product-aware billing providers found.');
            return;
        }

        if (empty($products)) {
            $this->info('No products found.');
            return;
        }

        $this->info(sprintf('Syncing %d products with %d providers', count($products), count($providers)));

        $results = [];
        $this->withProgressBar($providers, function (ProductAwareBillingProvider $provider) use ($products, &$results) {
            $result = $provider->syncProducts($products);
            $results[] = [
                'provider' => get_class($provider),
                'result' => $result
            ];
        });

        $this->newLine(2);

        foreach ($results as $providerResult) {
            $provider = class_basename($providerResult['provider']);
            $result = $providerResult['result'];

            if ($result->hasFailures()) {
                $this->error(sprintf('Provider "%s" had sync failures:', $provider));
                $this->table(['Failed Product IDs'], array_map(fn($id) => [$id], $result->failedProductIds()));
                $this->newLine();
                $this->table(['Failed Price IDs'], array_map(fn($id) => [$id], $result->failedPriceIds()));
            } else {
                $this->info(sprintf('✓ Provider "%s" synced successfully', $provider));
            }
        }
    }
}
