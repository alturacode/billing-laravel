<?php

use AlturaCode\Billing\Core\Subscriptions\SubscriptionStatus;
use Workbench\App\Models\User;

it('can create a free subscription', function () {
    $user = User::factory()->create();

    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    expect($user->subscribed())->toBeTrue()
        ->and($result->subscription)->toBeInstanceOf(\AlturaCode\Billing\Laravel\Subscription::class)
        ->and($result->subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($result->subscription->items)->toHaveCount(1);

    $this->assertDatabaseHas('subscriptions', [
        'id' => $result->result->subscription->id()->value(),
        'status' => $result->result->subscription->status()->value,
        'billable_id' => $user->id,
        'billable_type' => $user->getMorphClass(),
        'provider' => config('billing.provider'),
        'name' => 'default',
        'cancel_at_period_end' => false,
        'trial_ends_at' => null,
        'canceled_at' => null,
    ]);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $result->result->subscription->id()->value(),
        'price_id' => '01KC0PVCBTXR73W2XDZZ2R7F05',
        'quantity' => 1,
        'price_amount' => 0,
        'price_currency' => 'usd',
        'interval_type' => 'month',
        'interval_count' => 1,
    ]);
});

it('can create a paid subscription with addons', function () {
    $user = User::factory()->create();

    $result = $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK1')
        ->withAddon('01KBZ5R52MW2W6DY91FC8BEYK5')
        ->withAddon('01KBZ5R52MW2W6DY91FC8BEYK6', 10)
        ->create();

    expect($user->subscribed())->toBeTrue()
        ->and($result->subscription)->toBeInstanceOf(\AlturaCode\Billing\Laravel\Subscription::class)
        ->and($result->subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($result->subscription->items)->toHaveCount(3);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $result->result->subscription->id()->value(),
        'price_id' => '01KBZ5R52MW2W6DY91FC8BEYK1',
        'quantity' => 1,
        'price_amount' => 1500,
        'price_currency' => 'usd',
        'interval_type' => 'month',
        'interval_count' => 1,
    ]);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $result->result->subscription->id()->value(),
        'price_id' => '01KBZ5R52MW2W6DY91FC8BEYK5',
        'quantity' => 1,
        'price_amount' => 1000,
        'price_currency' => 'usd',
        'interval_type' => 'month',
        'interval_count' => 1,
    ]);

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $result->result->subscription->id()->value(),
        'price_id' => '01KBZ5R52MW2W6DY91FC8BEYK6',
        'quantity' => 10,
        'price_amount' => 800,
        'price_currency' => 'usd',
        'interval_type' => 'month',
        'interval_count' => 1,
    ]);
});

it('throws exception when passing a non-registered billing provider', function () {
    $user = User::factory()->create();

    expect(fn() => $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK1')
        ->withProvider('foo')
        ->create())->toThrow(InvalidArgumentException::class, "Billing provider [foo] is not registered.");
});
