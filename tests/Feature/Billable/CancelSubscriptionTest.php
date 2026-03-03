<?php

use AlturaCode\Billing\Core\Subscriptions\SubscriptionStatus;
use Workbench\App\Models\User;

it('can cancel a subscription immediately', function () {
    $user = User::factory()->create();

    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    $subscription = $result->subscription;

    expect($subscription->isCanceled())->toBeFalse();

    $subscription->cancel();

    $subscription->refresh();

    expect($subscription->isCanceled())->toBeTrue()
        ->and($subscription->status)->toBe(SubscriptionStatus::Canceled)
        ->and($subscription->canceled_at)->not->toBeNull();

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::Canceled->value,
    ]);
});

it('can cancel a subscription at the end of the period', function () {
    $user = User::factory()->create();

    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    $subscription = $result->subscription;

    expect($subscription->isPendingCancellation())->toBeFalse();

    $subscription->cancelAtPeriodEnd();

    $subscription->refresh();

    expect($subscription->isPendingCancellation())->toBeTrue()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->canceled_at)->toBeNull();

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'cancel_at_period_end' => true,
        'status' => SubscriptionStatus::Active->value,
    ]);
});

it('can undo a subscription cancellation at the end of the period', function () {
    $user = User::factory()->create();

    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    $subscription = $result->subscription;

    $subscription->cancelAtPeriodEnd();

    expect($subscription->refresh()->isPendingCancellation())->toBeTrue();

    $subscription->resume();

    $subscription->refresh();

    expect($subscription->isPendingCancellation())->toBeFalse()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'cancel_at_period_end' => false,
        'status' => SubscriptionStatus::Active->value,
    ]);
});
