# Changelog

All notable changes to `alturacode/billing-laravel` will be documented in this file.

## Unreleased

### Added

- Added `EloquentUsageLedger`, a Laravel database-backed implementation of core v0.24's `UsageLedger`.
- Added the `feature_usage_events` table for raw usage events.
- Added legacy `feature_usages` aggregate backfill into synthetic usage events.
- Added `FeatureUsageEvent` Eloquent model for persisted ledger events.
- Added `Billable::newUsageEvent()` and `HasBilling::newUsageEvent()` for Laravel-friendly usage recording.
- Added `UsageEventBuilder` with fluent `withId()`, `withAmount()`, `withMetadata()`, `withRecordedAt()`, and `record()` methods.

### Changed

- Usage tracking now follows `alturacode/billing-core` v0.24's ledger-first model.
- `features()` remains read-only and returns the core `UsageAwareEntitlementChecker`.
- Usage writes should now use `$billable->newUsageEvent('feature_key')->record()`.
- The config repository key changed from `billing.repositories.usage` to `billing.repositories.usage_ledger`.

### Removed

- Removed `EloquentUsageRepository`.
- Removed support for the removed core `UsageRepository` abstraction.
- Removed old consume-style usage helpers from the supported API surface:
  - `tryConsume()`
  - `incrementUsage()`
  - `decrementUsage()`
  - `setUsedAmount()`

### Notes

- This is a breaking change for applications that recorded usage through `features()->tryConsume()` or custom `UsageRepository` bindings.
- Recording usage no longer performs entitlement checks. Check with `features()->canUse()` before recording when your application needs limit enforcement.
