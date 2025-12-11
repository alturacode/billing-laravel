<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Features\Feature;
use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductRepository;
use AlturaCode\Billing\Core\Products\ProductSlug;
use Illuminate\Contracts\Config\Repository;

final readonly class ConfigProductRepository implements ProductRepository
{
    public function __construct(private Repository $config)
    {
    }

    public function all(): array
    {
        $plans = $this->plansFromConfig();
        $addons = $this->addOnsFromConfig();
        $products = $this->denormalize([...$plans, ...$addons]);
        return array_map(fn(array $product) => Product::hydrate($product), $products);
    }

    public function find(ProductId $productId): ?Product
    {
        return array_find($this->all(), fn(Product $product) => $product->id()->equals($productId));
    }

    public function findByPriceId(ProductPriceId $priceId): ?Product
    {
        return array_find($this->all(), fn(Product $product) => $product->hasPrice($priceId));
    }

    public function findBySlug(ProductSlug $slug): Product
    {
        return array_find($this->all(), fn(Product $product) => $product->slug()->equals($slug));
    }

    public function findMultipleByPriceIds(array $priceIds): array
    {
        return array_filter($this->all(), fn(Product $product) => $product->hasAnyPrice(...$priceIds));
    }

    public function save(Product $product): void
    {
    }

    /**
     * @return Product[]|array
     */
    public function plansFromConfig(): array
    {
        return array_map(fn($plan) => [...$plan, 'kind' => ProductKind::Plan->value], $this->config->get('billing.plans') ?? []);
    }

    /**
     * @return Product[]|array
     */
    public function addOnsFromConfig(): array
    {
        return array_map(fn($addon) => [...$addon, 'kind' => ProductKind::AddOn->value], $this->config->get('billing.addons') ?? []);
    }

    private function denormalize(array $products): array
    {
        // Products coming from the config file don't include the feature's kind, we need to add it.
        /** @var array<string, array> $map */
        $map = [];
        $features = $this->config->get('billing.features');
        foreach ($features as $feature) {
            $map[$feature['key']] = $feature;
        }
        return array_map(fn(array $product) => [
            ...$product,
            'features' => array_map(fn(array $feature) => [
                'key' => $feature['key'],
                'name' => $feature['name'] ?? $map[$feature['key']]['name'] ?? null,
                'description' => $feature['description'] ?? $map[$feature['key']]['description'] ?? null,
                'value' => [
                    'value' => $feature['value'],
                    'kind' => $map[$feature['key']]['kind'],
                ]
            ], $product['features'])
        ], $products);
    }
}