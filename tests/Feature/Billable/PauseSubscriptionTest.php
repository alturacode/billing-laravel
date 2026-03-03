<?php

use AlturaCode\Billing\Core\Subscriptions\SubscriptionStatus;
use Workbench\App\Models\User;

it('can pause a subscription', function () {
    $user = User::factory()->create();

    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    $subscription = $result->subscription;

    expect($subscription->isPaused())->toBeFalse();

    $subscription->pause();

    $subscription->refresh();

    expect($subscription->isPaused())->toBeTrue()
        ->and($subscription->status)->toBe(SubscriptionStatus::Paused);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::Paused->value,
    ]);
});

it('can resume a paused subscription', function () {
    $user = User::factory()->create();

    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    $subscription = $result->subscription;

    $subscription->pause();

    expect($subscription->refresh()->isPaused())->toBeTrue();

    $subscription->resume();

    $subscription->refresh();

    expect($subscription->isPaused())->toBeFalse()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::Active->value,
    ]);
});
