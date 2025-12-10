<?php

use Workbench\App\Models\User;

it('can check features', function () {
    $user = User::factory()->create();

    $user->newSubscription()
        ->withPlanPriceId('01KBZ5R52MW2W6DY91FC8BEYK1')
        ->create();

    expect($user->features()->canUse('priority_support'))->toBeTrue()
        ->and($user->features()->canUse('users'))->toBeTrue();
});