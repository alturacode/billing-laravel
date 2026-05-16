<?php

use AlturaCode\Billing\Core\Features\UsageEventId;
use AlturaCode\Billing\Core\UsageAwareEntitlementCheckerFactory;
use AlturaCode\Billing\Laravel\FeatureUsageEvent;
use Workbench\App\Models\User;

function recordUsageFor(User $user, string $featureKey, int $amount = 1): void
{
    $user->newUsageEvent($featureKey)
        ->withAmount($amount)
        ->record();
}

it('can check features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK1') // Pro plan
        ->create();

    expect($user->features()->canUse('priority_support'))->toBeTrue()
        ->and($user->features()->canUse('users'))->toBeTrue();
});

it('can check features with recorded usage limits', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05') // Free plan (2 users)
        ->create();

    expect($user->features()->canUse('users'))->toBeTrue();

    recordUsageFor($user, 'users');

    expect($user->features()->getUsedAmount('users'))->toBe(1)
        ->and($user->features()->canUse('users'))->toBeTrue();

    recordUsageFor($user, 'users');

    expect($user->features()->getUsedAmount('users'))->toBe(2)
        ->and($user->features()->canUse('users'))->toBeFalse();
});

it('can resolve the usage-aware entitlement checker factory', function () {
    expect(app(UsageAwareEntitlementCheckerFactory::class))->toBeInstanceOf(UsageAwareEntitlementCheckerFactory::class);
});

it('can record usage events without a subscription', function () {
    $user = User::factory()->create();
    $event = $user->newUsageEvent('projects');

    expect($event->record())->toBeTrue()
        ->and($event->record())->toBeFalse();

    $this->assertDatabaseHas('feature_usage_events', [
        'billable_type' => $user->getMorphClass(),
        'billable_id' => (string) $user->getKey(),
        'feature_key' => 'projects',
        'amount' => 1,
    ]);
});

it('can record usage events with custom amount metadata and timestamp', function () {
    $user = User::factory()->create();

    $user->newUsageEvent('tickets')
        ->withAmount(7)
        ->withMetadata(['source' => 'import'])
        ->withRecordedAt(new DateTimeImmutable('2026-02-10 12:00:00', new DateTimeZone('America/Puerto_Rico')))
        ->record();

    $event = FeatureUsageEvent::query()->where('feature_key', 'tickets')->first();

    expect($event->amount)->toBe(7)
        ->and($event->metadata)->toBe(['source' => 'import'])
        ->and($event->recorded_at->timezone('UTC')->format('Y-m-d H:i:s'))->toBe('2026-02-10 16:00:00');
});

it('can record usage events idempotently with a custom id', function () {
    $user = User::factory()->create();
    $eventId = UsageEventId::generate()->value();

    $event = $user->newUsageEvent('projects')
        ->withId($eventId)
        ->withAmount(3);

    expect($event->record())->toBeTrue()
        ->and($event->record())->toBeFalse();

    expect(FeatureUsageEvent::query()->where('id', $eventId)->count())->toBe(1);
});

it('handles unlimited features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK3') // Enterprise plan (unlimited projects)
        ->create();

    expect($user->features()->canUse('projects'))->toBeTrue()
        ->and($user->features()->canUse('projects', 1000))->toBeTrue()
        ->and($user->features()->getUsedAmount('projects'))->toBe(0);
});

it('returns false for unknown features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    expect($user->features()->canUse('non_existent'))->toBeFalse()
        ->and($user->features()->getUsedAmount('non_existent'))->toBe(0);
});
