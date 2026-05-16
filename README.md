# Altura Code Billing for Laravel

Altura Code Billing for Laravel gives you a clean way to manage subscriptions in your Laravel applications.

***This package is still under active development.***

## The Motivation

We created this package because we needed to support multiple billing providers (Stripe & PayPal) in one of our projects. Laravel Cashier, while great, only supports either Stripe or Paddle.

This package acts as a nice wrapper for [Altura Code Billing](https://github.com/alturacode/billing-core), giving us a
nice API to manage subscriptions in our Laravel apps.

We went a little further and made purchasable products as part of the core package; this allows you to easily define
products, prices and features right from a config file. You can optionally sync with billing providers like Stripe to get started quickly.

## TL;DR

```php
use AlturaCode\Billing\Laravel\HasBilling;
use AlturaCode\Billing\Laravel\Billable;

class User extends Model implements Billable {
    use HasBilling;
}

// Create a subscription
$result = $user->newSubscription('default')
    ->withPlan('plan_id', 'month', 1, 'usd') // or withPlanPriceId('product_price_id')
    ->withTrialDays(14)
    ->create();

if ($result->requiresAction()) {
    return $result->redirect();
}

$subscription = $result->subscription; // AlturaCode\Billing\Laravel\Subscription
```

## Requirements

- PHP 8.4+
- Laravel 12.x

## Upgrading

See [UPGRADE.md](UPGRADE.md) for breaking usage API changes and migration steps.

## Installation

Install the package

```
composer require alturacode/billing-laravel
```

Make your billable model implement `AlturaCode\Billing\Laravel\Billable` and use the `AlturaCode\Billing\Laravel\HasBilling` trait:

```php
use AlturaCode\Billing\Laravel\HasBilling;
use AlturaCode\Billing\Laravel\Billable;

class User extends Model implements Billable
{
    use HasBilling;
}
```

Publish the config file and migrations

```
php artisan vendor:publish --provider="AlturaCode\Billing\Laravel\BillingServiceProvider"
```

## Quick start

Create a subscription for a user:

```php
// In a controller action
$result = $request->user()->newSubscription('default')
    ->withPlanPriceId('price_basic_monthly', quantity: 1)
    ->withTrialDays(14)
    ->create();

if ($result->requiresAction()) {
    return $result->redirect();
}

$subscription = $result->subscription; // AlturaCode\Billing\Laravel\Subscription
```

Check a user’s subscription status:

```php
if ($user->subscribed()) {
    // has an active default subscription
}

$sub = $user->subscription('default'); // Eloquent model or null
```

Check if a user can use a feature:

```php
$user->features()->canUse('projects', 3); // boolean
```

Inspect recorded usage for a feature:

```php
$user->features()->getUsedAmount('projects'); // integer
```

Record usage:

```php
$user->newUsageEvent('projects')
    ->withMetadata(['source' => 'project_created'])
    ->record();
```

Use a stable event id for retry-safe recording:

```php
$user->newUsageEvent('projects')
    ->withId($requestId)
    ->record();
```

Configure the usage ledger storage in `config/billing.php`:

```php
'repositories' => [
    'usage_ledger' => AlturaCode\Billing\Laravel\EloquentUsageLedger::class,
],
```

Query subscriptions:

```php
use AlturaCode\Billing\Laravel\Subscription;

$active = Subscription::query()
    ->provider('sync')
    ->active()
    ->get();
```

## Stripe

Install the Stripe package:

```
composer require alturacode/billing-stripe
```

Register the billing provider in `config/billing.php`:

```php
'providers' => [
    // ...
    'stripe' => AlturaCode\Billing\Stripe\StripeBillingProvider::class,
]
```

Use Stripe for subscriptions:

```php
$user->newSubscription('default')
    ->withPlanPriceId('price_basic_monthly')
    ->withProvider('stripe')
    ->create([
       'success_url' => 'https://example.com/success',
        'cancel_url' => 'https://example.com/cancel',
   ]);
```

## License

MIT License. See `LICENSE` for details.
