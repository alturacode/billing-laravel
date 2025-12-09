# Altura Code Billing for Laravel

Altura Code Billing for Laravel gives you a clean, Eloquent‑friendly way to work with subscriptions, products, features,
and billing providers in Laravel apps.

## TL;DR

```php
// Add the Billable trait to your billable model (e.g., User).
use AlturaCode\Billing\Laravel\Billable;

class User extends Model {
    use Billable;
}

// Create a subscription
$result = $user->newSubscription('default')
    ->withPlanPriceId('price_basic_monthly')
    ->withTrialDays(14)
    ->create();

if ($result->requiresAction()) {
    return $result->redirect(); // e.g., off-site checkout or SCA
}

$subscription = $result->subscription; // AlturaCode\Billing\Laravel\Subscription
```

The default provider is a synchronous in-memory provider (great for demos and tests). Swap it for a real provider by
implementing `BillingProvider` from the core package and wiring it in the config.

## Requirements

- PHP 8.4+
- Laravel 12.x

## Installation

1) Install the package

```
composer require alturacode/billing-laravel
```

The service provider is auto-discovered.

2) Add the `Billable` trait to your billable model (usually `App\Models\User`)

```php
use AlturaCode\Billing\Laravel\Billable;

class User extends Model
{
    use Billable;
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

## High-level API surface

- Trait: `AlturaCode\Billing\Laravel\Billable`
    - `subscription(string $name = 'default'): ?Subscription`
    - `subscriptions()` Eloquent relation (morphMany)
    - `subscribed(string $name = 'default'): bool`
    - `newSubscription(string $name = 'default'): SubscriptionBuilder`

- Builder: `AlturaCode\Billing\Laravel\SubscriptionBuilder` (delegates to Core `SubscriptionDraftBuilder`)
    - `withName(string $name)`
    - `withBillable(string $billableType, string $billableId)`
    - `withProvider(string $provider)`
    - `withPlanPriceId(string $priceId, int $quantity = 1)`
    - `withTrialEndsAt(DateTimeImmutable|null $trialEndsAt)`
    - `withTrialDays(int $days)`
    - `withAddon(string $priceId, int $quantity = 1)`
    - `create(array $providerOptions = []): AlturaCode\Billing\Laravel\BillingProviderResult`

- Models:
    - `AlturaCode\Billing\Laravel\Subscription` (Eloquent)
        - Relations: `items()`, `billable()`
        - Helpers: `isActive()`, `isPaused()`, `isCanceled()`, `isIncomplete()`
        - Scopes: `provider()`, `name()`, `active()`, `paused()`, `canceled()`, `incomplete()`
        - Conversion: `toCore()` -> Core `AlturaCode\Billing\Core\Subscriptions\Subscription`
    - `AlturaCode\Billing\Laravel\SubscriptionItem` (Eloquent)
        - Conversion: `toCore()` -> Core `AlturaCode\Billing\Core\Subscriptions\SubscriptionItem`

- Result:
    - Core `AlturaCode\Billing\Core\Provider\BillingProviderResult`
        - Properties: `subscription`, `clientAction`
        - Methods: `requiresAction()`
    - Laravel convenience wrapper `AlturaCode\Billing\Laravel\BillingProviderResult` adds `redirect()` (when used) and
      `subscription` property for retrieving the Eloquent subscription model.

- Service provider bindings:
    - `BillingProviderRegistry` is built from `config('billing.providers')`.
    - Repositories are bound from `config('billing.repositories.*')`.

## Providers

The package ships with `SynchronousBillingProvider` (no external calls). To integrate with a real provider:

1) Implement the Core interface `AlturaCode\Billing\Core\Provider\BillingProvider` in your app (e.g.
   `App\Billing\StripeProvider`).
2) Add it to `config('billing.providers')` and set `config('billing.provider')` to its key.
3) In your implementation, return a `BillingProviderResult::redirect(...)` when a client action (e.g. checkout) is
   required, or `BillingProviderResult::completed(...)` when done.

## License

MIT License. See `LICENSE` for details.