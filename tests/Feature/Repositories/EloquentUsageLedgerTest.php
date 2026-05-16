<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageEventId;
use AlturaCode\Billing\Laravel\EloquentUsageLedger;
use AlturaCode\Billing\Laravel\FeatureUsageEvent;

function usageWindow(string $start = '2026-01-01 00:00:00', string $end = '2026-02-01 00:00:00'): UsageWindow
{
    return UsageWindow::create(
        new DateTimeImmutable($start, new DateTimeZone('UTC')),
        new DateTimeImmutable($end, new DateTimeZone('UTC'))
    );
}

function usageEvent(
    BillableIdentity $billable,
    FeatureKey $featureKey,
    int $amount,
    string $recordedAt = '2026-01-15 00:00:00',
    ?UsageEventId $id = null,
): UsageEvent {
    return UsageEvent::create(
        $id ?? UsageEventId::generate(),
        $billable,
        $featureKey,
        $amount,
        new DateTimeImmutable($recordedAt, new DateTimeZone('UTC')),
        ['source' => 'test']
    );
}

it('records usage events by billable identity', function () {
    $ledger = new EloquentUsageLedger();
    $billable = BillableIdentity::fromString('users', 'user-1');
    $featureKey = FeatureKey::fromString('api_calls');

    expect($ledger->getUsedAmount($billable, $featureKey, usageWindow()))->toBe(0);
    expect($ledger->record(usageEvent($billable, $featureKey, 3)))->toBeTrue();
    expect($ledger->getUsedAmount($billable, $featureKey, usageWindow()))->toBe(3);

    $record = FeatureUsageEvent::query()->first();

    expect($record)->not->toBeNull();
    expect($record->billable_type)->toBe('users');
    expect($record->billable_id)->toBe('user-1');
    expect($record->metadata)->toBe(['source' => 'test']);
});

it('deduplicates usage events by event id', function () {
    $ledger = new EloquentUsageLedger();
    $billable = BillableIdentity::fromString('users', 'user-1');
    $featureKey = FeatureKey::fromString('api_calls');
    $eventId = UsageEventId::generate();

    expect($ledger->record(usageEvent($billable, $featureKey, 3, id: $eventId)))->toBeTrue();
    expect($ledger->record(usageEvent($billable, $featureKey, 7, id: $eventId)))->toBeFalse();
    expect($ledger->getUsedAmount($billable, $featureKey, usageWindow()))->toBe(3);
});

it('sums events inside the requested window', function () {
    $ledger = new EloquentUsageLedger();
    $billable = BillableIdentity::fromString('teams', 'team-1');
    $featureKey = FeatureKey::fromString('projects');

    $ledger->record(usageEvent($billable, $featureKey, 2, '2026-01-10 00:00:00'));
    $ledger->record(usageEvent($billable, $featureKey, 5, '2026-01-20 00:00:00'));
    $ledger->record(usageEvent($billable, $featureKey, 11, '2026-02-10 00:00:00'));

    expect($ledger->getUsedAmount($billable, $featureKey, usageWindow()))->toBe(7);
    expect($ledger->getUsedAmount($billable, $featureKey, usageWindow('2026-02-01 00:00:00', '2026-03-01 00:00:00')))->toBe(11);
});

it('isolates usage across billables and features', function () {
    $ledger = new EloquentUsageLedger();
    $billableA = BillableIdentity::fromString('teams', 'team-1');
    $billableB = BillableIdentity::fromString('teams', 'team-2');
    $projects = FeatureKey::fromString('projects');
    $tickets = FeatureKey::fromString('tickets');

    $ledger->record(usageEvent($billableA, $projects, 2));
    $ledger->record(usageEvent($billableB, $projects, 5));
    $ledger->record(usageEvent($billableA, $tickets, 9));

    expect($ledger->getUsedAmount($billableA, $projects, usageWindow()))->toBe(2);
    expect($ledger->getUsedAmount($billableB, $projects, usageWindow()))->toBe(5);
    expect($ledger->getUsedAmount($billableA, $tickets, usageWindow()))->toBe(9);
});
