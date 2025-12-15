<?php

use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Products\ProductSlug;
use AlturaCode\Billing\Core\Provider\ProductAwareBillingProvider;
use AlturaCode\Billing\Core\Provider\ProductSyncResult;
use AlturaCode\Billing\Laravel\BillingProviderRegistry;

function makeProduct(string $id, string $slug, string $name = 'Test', string $desc = 'Desc'): Product
{
    return Product::create(
        ProductId::fromString($id),
        ProductKind::Plan,
        ProductSlug::fromString($slug),
        $name,
        $desc
    );
}

it('prints message when there are no product-aware providers', function () {
    // Bind an empty registry
    app()->instance(\AlturaCode\Billing\Core\Provider\BillingProviderRegistry::class, new BillingProviderRegistry([]));

    $this->artisan('billing:sync-products')
        ->expectsOutput('No product-aware billing providers found.')
        ->assertExitCode(0);
});

it('prints message when there are no products', function () {
    app('config')->set('billing.plans', []);
    app('config')->set('billing.addons', []);

    $this->artisan('billing:sync-products')
        ->expectsOutput('No products found.')
        ->assertExitCode(0);
});

it('syncs products and reports per-provider results', function () {
    // Products
    $prod1 = '01KBZ5R52MW2W6DY91FC8BEYK1';
    $prod2 = '01KBZ5R52MW2W6DY91FC8BEYK2';
    $products = [
        makeProduct($prod1, 'prod_1'),
        makeProduct($prod2, 'prod_2'),
    ];

    // Provider A: success
    $providerA = Mockery::mock(ProductAwareBillingProvider::class);
    $providerA->shouldReceive('syncProducts')
        ->once()
        ->with($products)
        ->andReturnUsing(function ($products) {
            $result = ProductSyncResult::makeEmpty();
            foreach ($products as $p) {
                $result = $result->markSyncedProduct($p->id()->value(), 'prov_' . $p->id()->value());
            }
            return $result;
        });

    // Provider B: failures
    $providerB = Mockery::mock(ProductAwareBillingProvider::class);
    $providerB->shouldReceive('syncProducts')
        ->once()
        ->with($products)
        ->andReturnUsing(function ($products) {
            $result = ProductSyncResult::makeEmpty();
            foreach ($products as $p) {
                $result = $result->markFailedProduct($p->id()->value(), 'error');
            }
            return $result;
        });

    app()->instance(\AlturaCode\Billing\Core\Provider\BillingProviderRegistry::class, new BillingProviderRegistry([$providerA, $providerB]));

    $mockRepository = Mockery::mock(ProductRepository::class);
    $mockRepository->shouldReceive('all')->andReturn($products);
    $mockRepository->shouldReceive('find')->andReturn(null);
    $mockRepository->shouldReceive('findByPriceId')->andReturn(null);
    $mockRepository->shouldReceive('findBySlug')->andReturn(null);
    $mockRepository->shouldReceive('findMultipleByPriceIds')->andReturn([]);
    $mockRepository->shouldReceive('save')->andReturnNull();

    app()->instance(ProductRepository::class, $mockRepository);

    $this->artisan('billing:sync-products')
        ->expectsOutputToContain('Syncing 2 products with 2 providers')
        ->expectsOutputToContain('synced successfully')
        ->expectsOutputToContain('had sync failures:')
        ->expectsOutputToContain('Failed Product IDs')
        ->expectsOutputToContain('error')
        ->assertExitCode(0);
});
