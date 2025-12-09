<?php

namespace Tests;

use AlturaCode\Billing\Laravel\BillingLaravelServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BillingLaravelServiceProvider::class,
        ];
    }
}
