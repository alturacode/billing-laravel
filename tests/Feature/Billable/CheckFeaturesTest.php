<?php

use Workbench\App\Models\User;

it('can check features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK1') // Pro plan
        ->create();

    expect($user->features()->canUse('priority_support'))->toBeTrue()
        ->and($user->features()->canUse('users'))->toBeTrue();
});

it('can check features with limits', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05') // Free plan (2 users)
        ->create();

    expect($user->features()->canUse('users'))->toBeTrue()
        ->and($user->features()->tryConsume('users'))->toBeTrue()
        ->and($user->features()->getUsedAmount('users'))->toBe(1)
        ->and($user->features()->tryConsume('users'))->toBeTrue()
        ->and($user->features()->getUsedAmount('users'))->toBe(2)
        ->and($user->features()->canUse('users'))->toBeFalse();
});

it('can consume features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05') // Free plan (2 users)
        ->create();

    expect($user->features()->tryConsume('users'))->toBeTrue()
        ->and($user->features()->getUsedAmount('users'))->toBe(1)
        ->and($user->features()->tryConsume('users'))->toBeTrue()
        ->and($user->features()->getUsedAmount('users'))->toBe(2)
        ->and($user->features()->tryConsume('users'))->toBeFalse()
        ->and($user->features()->getUsedAmount('users'))->toBe(2);
});

it('can manage usage directly', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05') // Free plan
        ->create();

    $user->features()->incrementUsage('users', 1);
    expect($user->features()->getUsedAmount('users'))->toBe(1);

    $user->features()->incrementUsage('users', 1);
    expect($user->features()->getUsedAmount('users'))->toBe(2);

    $user->features()->decrementUsage('users', 1);
    expect($user->features()->getUsedAmount('users'))->toBe(1);

    $user->features()->setUsedAmount('users', 5);
    expect($user->features()->getUsedAmount('users'))->toBe(5);
});

it('handles unlimited features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK3') // Enterprise plan (unlimited projects)
        ->create();

    expect($user->features()->canUse('projects'))->toBeTrue()
        ->and($user->features()->tryConsume('projects', 1000))->toBeTrue()
        ->and($user->features()->getUsedAmount('projects'))->toBe(0);
});

it('returns false for unknown features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KC0PVCBTXR73W2XDZZ2R7F05')
        ->create();

    expect($user->features()->canUse('non_existent'))->toBeFalse()
        ->and($user->features()->tryConsume('non_existent'))->toBeFalse();
});