<?php

use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductSlug;
use AlturaCode\Billing\Laravel\ConfigProductRepository;

it('can get all products from config', function () {
    $repository = app(ConfigProductRepository::class);
    $products = $repository->all();

    expect($products)->toBeArray()
        ->and($products)->not->toBeEmpty()
        ->and($products[0])->toBeInstanceOf(Product::class);

    $slugs = array_map(fn(Product $p) => $p->slug()->value(), $products);
    expect($slugs)->toContain('free', 'pro', 'enterprise', 'extra_storage', 'extra_users');
});

it('can find a product by id', function () {
    $repository = app(ConfigProductRepository::class);

    // Free plan ID from config
    $id = '01KBZ5QVFC48JBW3P9V61CKMF1';
    $product = $repository->find(ProductId::fromString($id));

    expect($product)->not->toBeNull()
        ->and($product->id()->value())->toBe($id)
        ->and($product->slug()->value())->toBe('free');
});

it('can find an addon by id', function () {
    $repository = app(ConfigProductRepository::class);

    // Extra Storage ID from config
    $id = '01KBZ5Q3WG725KV8ZH1Z6Y6HP1';
    $product = $repository->find(ProductId::fromString($id));

    expect($product)->not->toBeNull()
        ->and($product->id()->value())->toBe($id)
        ->and($product->slug()->value())->toBe('extra_storage')
        ->and($product->kind()->value)->toBe('addon');
});

it('can find a product by price id', function () {
    $repository = app(ConfigProductRepository::class);

    // Free plan price ID from config
    $id = '01KC0PVCBTXR73W2XDZZ2R7F05';
    $product = $repository->findByPriceId(ProductPriceId::fromString($id));

    expect($product)->not->toBeNull()
        ->and($product->slug()->value())->toBe('free');
});

it('can find a product by slug', function () {
    $repository = app(ConfigProductRepository::class);

    $product = $repository->findBySlug(ProductSlug::fromString('pro'));

    expect($product)->not->toBeNull()
        ->and($product->slug()->value())->toBe('pro')
        ->and($product->name())->toBe('Pro');
});

it('properly denormalizes features for products', function () {
    $repository = app(ConfigProductRepository::class);

    $product = $repository->findBySlug(ProductSlug::fromString('free'));

    $features = $product->features();
    expect($features)->not->toBeEmpty();

    $storageFeature = array_find($features, fn($f) => $f->key()->value() === 'storage_space');

    expect($storageFeature)->not->toBeNull()
        ->and($storageFeature->name())->toBe('Storage Space') // Loaded from billing.features
        ->and($storageFeature->value()->value())->toBe(5); // Loaded from the plan itself
});

it('returns empty array if no plans or addons in config', function () {
    config(['billing.plans' => [], 'billing.addons' => []]);
    $repository = app(ConfigProductRepository::class);

    expect($repository->all())->toBeEmpty();
});

it('throws exception if a product feature is not defined in billing.features', function () {
    config([
        'billing.features' => [],
        'billing.plans' => [
            [
                'id' => 'plan_1',
                'name' => 'Plan 1',
                'description' => 'Description',
                'slug' => 'plan-1',
                'prices' => [],
                'features' => [
                    ['key' => 'undefined_feature', 'value' => 10]
                ]
            ]
        ]
    ]);

    $repository = app(ConfigProductRepository::class);
    $repository->all();
})->throws(Exception::class);
