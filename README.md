# Altura Code Billing for Laravel

Altura Code Billing for Laravel gives you a clean, Eloquent‑friendly way to work with subscriptions, products, features,
and billing providers in Laravel apps.

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
    return $result->redirect(); // e.g., off-site checkout or SCA
}

$subscription = $result->subscription; // AlturaCode\Billing\Laravel\Subscription
```

## Requirements

- PHP 8.4+
- Laravel 12.x

## Installation

1) Install the package

```
composer require alturacode/billing-laravel
```

The service provider is auto-discovered.

2) Make your billable model implement `AlturaCode\Billing\Laravel\Billable` and use the `AlturaCode\Billing\Laravel\HasBilling` trait:

```php
use AlturaCode\Billing\Laravel\HasBilling;
use AlturaCode\Billing\Laravel\Billable;

class User extends Model implements Billable
{
    use HasBilling;
}
```

3) Publish the config file and migrations

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

Query subscriptions:

```php
use AlturaCode\Billing\Laravel\Subscription;

$active = Subscription::query()
    ->provider('sync')
    ->active()
    ->get();
```

## Billing Providers

The package ships with `SynchronousBillingProvider` (no external calls). To integrate with a real provider:

1) Implement the Core interface `AlturaCode\Billing\Core\Provider\BillingProvider` in your app (e.g.
   `App\Billing\StripeProvider`).
2) Add it to `config('billing.providers')` and set `config('billing.provider')` to its key.
3) In your implementation, return a `BillingProviderResult::redirect(...)` when a client action (e.g. checkout) is
   required, or `BillingProviderResult::completed(...)` when done.

## License

MIT License. See `LICENSE` for details.