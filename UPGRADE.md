# Upgrade Guide

## Upgrading to the ledger-first usage API

This release aligns `alturacode/billing-laravel` with `alturacode/billing-core` v0.24. Usage writes now go through `UsageLedger`, while `features()` is read-only.

## 1. Update the usage repository config key

If you publish `config/billing.php`, replace the old usage repository key:

```php
'repositories' => [
    'usage' => AlturaCode\Billing\Laravel\EloquentUsageRepository::class,
],
```

with:

```php
'repositories' => [
    'usage_ledger' => AlturaCode\Billing\Laravel\EloquentUsageLedger::class,
],
```

There is no fallback for `repositories.usage`.

## 2. Run the new migrations

Run your normal Laravel migrations:

```bash
php artisan migrate
```

This creates `feature_usage_events`, the new raw event ledger table.

Existing non-zero rows in `feature_usages` are backfilled into synthetic ledger events:

- event id uses the old `feature_usages.id`
- amount uses the old `used` value
- recorded time uses `window_start`
- metadata stores the legacy source and old window bounds

The old `feature_usages` table is left untouched for upgrade safety.

## 3. Replace old consume-style usage writes

Old API:

```php
if ($user->features()->tryConsume('projects')) {
    // action allowed and usage recorded
}
```

New API:

```php
if ($user->features()->canUse('projects')) {
    $user->newUsageEvent('projects')->record();

    // action allowed and usage recorded
}
```

Usage recording is no longer an atomic check-and-consume operation. If you need strict concurrency guarantees, enforce them in your application flow or custom `UsageLedger` storage backend.

## 4. Replace direct usage mutation helpers

Old API:

```php
$user->features()->incrementUsage('projects', 3);
$user->features()->decrementUsage('projects', 1);
$user->features()->setUsedAmount('projects', 10);
```

New API:

```php
$user->newUsageEvent('projects')
    ->withAmount(3)
    ->record();
```

The new ledger is append-only. There is no direct decrement or set operation. Model corrections as compensating events in your own domain if needed, or implement a custom ledger strategy.

## 5. Use stable ids for retry-safe writes

For webhook processing, queued jobs, imports, or any retryable action, pass a stable ULID-compatible id:

```php
$user->newUsageEvent('projects')
    ->withId($eventId)
    ->withMetadata(['source' => 'webhook'])
    ->record();
```

Calling `record()` again with the same id returns `false` and does not duplicate usage.

## 6. Replace custom usage storage

If your application bound a custom implementation of core `UsageRepository`, replace it with `AlturaCode\Billing\Core\Features\UsageLedger`.

Implement:

```php
use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageLedger;

final class DatabaseUsageLedger implements UsageLedger
{
    public function record(UsageEvent $event): bool
    {
        // Insert by $event->id()->value().
        // Return false when the id already exists.
    }

    public function getUsedAmount(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window): int
    {
        // Sum event amounts for the billable and feature where recorded_at is inside the window.
    }
}
```

Then configure:

```php
'repositories' => [
    'usage_ledger' => App\Billing\DatabaseUsageLedger::class,
],
```

## 7. Update tests

Replace assertions around `tryConsume()` with separate assertions:

- `features()->canUse()` checks authorization
- `newUsageEvent(...)->record()` records usage
- `features()->getUsedAmount()` reads windowed totals

Example:

```php
expect($user->features()->canUse('projects'))->toBeTrue();

$user->newUsageEvent('projects')->record();

expect($user->features()->getUsedAmount('projects'))->toBe(1);
```
