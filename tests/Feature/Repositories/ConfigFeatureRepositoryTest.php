<?php

use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Features\Feature;
use AlturaCode\Billing\Laravel\ConfigFeatureRepository;

it('can get all features from config', function () {
    $repository = app(ConfigFeatureRepository::class);
    $features = $repository->all();

    expect($features)->toBeArray()
        ->and($features)->not->toBeEmpty()
        ->and($features[0])->toBeInstanceOf(Feature::class);

    $keys = array_map(fn(Feature $f) => $f->key()->value(), $features);
    expect($keys)->toContain('storage_space', 'users', 'projects', 'priority_support', 'custom_domain');
});

it('can find a feature by key', function () {
    $repository = app(ConfigFeatureRepository::class);

    $feature = $repository->find(FeatureKey::fromString('storage_space'));

    expect($feature)->not->toBeNull()
        ->and($feature->key()->value())->toBe('storage_space')
        ->and($feature->name())->toBe('Storage Space')
        ->and($feature->isLimit())->toBeTrue();
});

it('returns null if feature is not found', function () {
    $repository = app(ConfigFeatureRepository::class);

    $feature = $repository->find(FeatureKey::fromString('non_existent'));

    expect($feature)->toBeNull();
});

it('returns empty array if no features in config', function () {
    config(['billing.features' => []]);
    $repository = app(ConfigFeatureRepository::class);

    expect($repository->all())->toBeEmpty();
});
