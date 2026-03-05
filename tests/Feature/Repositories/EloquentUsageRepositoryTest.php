<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Laravel\EloquentUsageRepository;
use AlturaCode\Billing\Laravel\FeatureUsage;

it('records usage by billable identity', function () {
    $repository = new EloquentUsageRepository();
    $billable = BillableIdentity::fromString('users', 'user-1');
    $featureKey = FeatureKey::fromString('api_calls');
    $window = UsageWindow::create(
        new DateTimeImmutable('2026-01-01 00:00:00'),
        new DateTimeImmutable('2026-02-01 00:00:00')
    );

    expect($repository->getUsedAmount($billable, $featureKey, $window))->toBe(0);
    expect($repository->tryConsume($billable, $featureKey, $window, 3, 10))->toBeTrue();
    expect($repository->getUsedAmount($billable, $featureKey, $window))->toBe(3);

    $record = FeatureUsage::query()->first();

    expect($record)->not->toBeNull();
    expect($record->billable_type)->toBe('users');
    expect($record->billable_id)->toBe('user-1');
});

it('separates usage across billables', function () {
    $repository = new EloquentUsageRepository();
    $featureKey = FeatureKey::fromString('projects');
    $window = UsageWindow::create(
        new DateTimeImmutable('2026-01-01 00:00:00'),
        new DateTimeImmutable('2026-02-01 00:00:00')
    );

    $billableA = BillableIdentity::fromString('teams', 'team-1');
    $billableB = BillableIdentity::fromString('teams', 'team-2');

    $repository->incrementUsage($billableA, $featureKey, $window, 2);
    $repository->incrementUsage($billableB, $featureKey, $window, 5);

    expect($repository->getUsedAmount($billableA, $featureKey, $window))->toBe(2);
    expect($repository->getUsedAmount($billableB, $featureKey, $window))->toBe(5);
});
