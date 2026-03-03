<?php

use AlturaCode\Billing\Laravel\Subscription;
use Workbench\App\Models\User;

it('can swap subscription item prices', function () {
    $user = User::factory()->create();

    // Create a free subscription
    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    $subscription = $result->subscription;
    $item = $subscription->items->first();

    // Swap to Pro plan (Monthly)
    $newPriceId = '01KBZ5R52MW2W6DY91FC8BEYK1';
    $subscription = $item->swap($newPriceId)->subscription;

    expect($subscription->items)->toHaveCount(1)
        ->and($subscription->items->first()->price_id)->toBe($newPriceId)
        ->and($subscription->items->first()->price_amount)->toBe(1500);

    $this->assertDatabaseHas('subscription_items', [
        'id' => $item->id,
        'price_id' => $newPriceId,
        'price_amount' => 1500,
    ]);

    // Ensure entitlements were updated (Pro plan features)
    $this->assertDatabaseHas('subscription_item_entitlements', [
        'subscription_item_id' => $item->id,
        'feature_key' => 'priority_support',
        'feature_value_boolean' => true,
    ]);
});

it('can swap subscription prices via subscription model', function () {
    $user = User::factory()->create();

    // Create a Pro (Monthly) subscription
    $result = $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK1')
        ->create();

    $subscription = $result->subscription;

    // Swap to Pro (Yearly)
    $newPriceId = '01KBZ5R52MW2W6DY91FC8BEYK2';
    $subscription = $subscription->swap($newPriceId)->subscription;

    expect($subscription->items)->toHaveCount(1)
        ->and($subscription->items->first()->price_id)->toBe($newPriceId)
        ->and($subscription->items->first()->price_amount)->toBe(15000)
        ->and($subscription->items->first()->interval_type)->toBe('year');

    $this->assertDatabaseHas('subscription_items', [
        'subscription_id' => $subscription->id,
        'price_id' => $newPriceId,
        'price_amount' => 15000,
        'interval_type' => 'year',
    ]);
});

it('properly persists changes when swapping prices', function () {
    $user = User::factory()->create();

    // Start with Free plan
    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    $subscription = $result->subscription;
    $item = $subscription->items->first();

    $initialEntitlementsCount = \AlturaCode\Billing\Laravel\SubscriptionItemEntitlement::count();

    // Swap to Enterprise (Monthly)
    $newPriceId = '01KBZ5R52MW2W6DY91FC8BEYK3';
    $item->swap($newPriceId);

    // Refresh from database
    $subscription->refresh();
    $item->refresh();

    expect($item->price_id)->toBe($newPriceId)
        ->and($item->price_amount)->toBe(5000);

    // Check entitlements persistence
    $this->assertDatabaseHas('subscription_item_entitlements', [
        'subscription_item_id' => $item->id,
        'feature_key' => 'custom_domain',
        'feature_value_boolean' => true,
    ]);

    $this->assertDatabaseHas('subscription_item_entitlements', [
        'subscription_item_id' => $item->id,
        'feature_key' => 'projects',
        'feature_value_integer' => null, // unlimited
    ]);

    // Ensure we don't have orphan entitlements
    // The number of entitlements should be equal to the number of features in the Enterprise plan (6)
    // plus any other subscriptions in other tests (but this test is isolated usually).
    // Given the SynchronousBillingProvider replaces entitlements with NEW IDs, 
    // the count should be 6 if orphans are deleted.
    $currentEntitlementsCount = \AlturaCode\Billing\Laravel\SubscriptionItemEntitlement::where('subscription_item_id', $item->id)->count();
    expect($currentEntitlementsCount)->toBe(6);
});

it('keeps addons when swapping the main plan price', function () {
    $user = User::factory()->create();

    // Create a subscription with an addon (Extra Storage)
    $result = $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05') // Free
        ->withAddon('01KBZ5R52MW2W6DY91FC8BEYK5') // Extra Storage
        ->create();

    $subscription = $result->subscription;
    expect($subscription->items)->toHaveCount(2);

    // Swap main plan to Pro
    $newPlanPriceId = '01KBZ5R52MW2W6DY91FC8BEYK1';
    $subscription = $subscription->swap($newPlanPriceId)->subscription;

    $subscription->refresh();
    expect($subscription->items)->toHaveCount(2);

    // Main plan should be updated
    $mainItem = $subscription->items->firstWhere('price_id', $newPlanPriceId);
    expect($mainItem)->not->toBeNull()
        ->and($mainItem->price_amount)->toBe(1500);

    // Addon should still be there
    $addonItem = $subscription->items->firstWhere('price_id', '01KBZ5R52MW2W6DY91FC8BEYK5');
    expect($addonItem)->not->toBeNull()
        ->and($addonItem->price_amount)->toBe(1000);
});