<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Products\Product;
use AlturaCode\Billing\Core\Products\ProductId;
use AlturaCode\Billing\Core\Products\ProductKind;
use AlturaCode\Billing\Core\Products\ProductPriceId;
use AlturaCode\Billing\Core\Products\ProductRepository;
use Illuminate\Contracts\Config\Repository;

final readonly class ConfigProductRepository implements ProductRepository
{
    public function __construct(private Repository $config)
    {
    }

    public function all(): array
    {
        $plans = array_map(fn($plan) => Product::hydrate([...$plan, 'kind' => ProductKind::Plan->value]), $this->config->get('billing.plans') ?? []);
        $addons = array_map(fn($addon) => Product::hydrate([...$addon, 'kind' => ProductKind::AddOn->value]), $this->config->get('billing.addons') ?? []);
        $products = [...$plans, ...$addons];

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

    public function findMultipleByPriceIds(array $priceIds): array
    {
        return array_filter($this->all(), fn(Product $product) => $product->hasAnyPrice($priceIds));
    }

    public function save(Product $product): void
    {
    }
}