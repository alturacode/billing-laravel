<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Features\UsageRepository;
use Illuminate\Support\Facades\DB;

final readonly class EloquentUsageRepository implements UsageRepository
{
    /**
     * @inheritDoc
     */
    public function getUsedAmount(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window): int
    {
        return FeatureUsage::query()
            ->where('billable_type', $billable->type())
            ->where('billable_id', (string) $billable->id())
            ->where('feature_key', $featureKey->value())
            ->where('window_start', $window->startsAt())
            ->where('window_end', $window->endsAt())
            ->value('used') ?? 0;
    }

     /**
     * @inheritDoc
     */
    public function tryConsume(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window, int $amount, int $limit): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return DB::transaction(function () use ($billable, $featureKey, $window, $amount, $limit) {
            $record = FeatureUsage::query()
                ->where('billable_type', $billable->type())
                ->where('billable_id', (string) $billable->id())
                ->where('feature_key', $featureKey->value())
                ->where('window_start', $window->startsAt())
                ->where('window_end', $window->endsAt())
                ->lockForUpdate()
                ->first();

            $currentUsage = $record?->used ?? 0;

            if ($currentUsage + $amount > $limit) {
                return false;
            }

            if ($record) {
                $record->increment('used', $amount);
            } else {
                FeatureUsage::create([
                    'billable_type' => $billable->type(),
                    'billable_id' => (string) $billable->id(),
                    'feature_key' => $featureKey->value(),
                    'window_start' => $window->startsAt(),
                    'window_end' => $window->endsAt(),
                    'used' => $amount,
                ]);
            }

            return true;
        });
    }

    public function setUsedAmount(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window, int $amount): void
    {
        FeatureUsage::query()->updateOrCreate(
            [
                'billable_type' => $billable->type(),
                'billable_id' => (string) $billable->id(),
                'feature_key' => $featureKey->value(),
                'window_start' => $window->startsAt(),
                'window_end' => $window->endsAt(),
            ],
            ['used' => $amount]
        );
    }

    public function incrementUsage(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window, int $amount): void
    {
        DB::transaction(function () use ($billable, $featureKey, $window, $amount) {
            $record = FeatureUsage::query()
                ->where('billable_type', $billable->type())
                ->where('billable_id', (string) $billable->id())
                ->where('feature_key', $featureKey->value())
                ->where('window_start', $window->startsAt())
                ->where('window_end', $window->endsAt())
                ->lockForUpdate()
                ->first();

            if ($record) {
                $record->increment('used', $amount);
            } else {
                FeatureUsage::create([
                    'billable_type' => $billable->type(),
                    'billable_id' => (string) $billable->id(),
                    'feature_key' => $featureKey->value(),
                    'window_start' => $window->startsAt(),
                    'window_end' => $window->endsAt(),
                    'used' => $amount,
                ]);
            }
        });
    }

    public function decrementUsage(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window, int $amount): void
    {
        DB::transaction(function () use ($billable, $featureKey, $window, $amount) {
            $record = FeatureUsage::query()
                ->where('billable_type', $billable->type())
                ->where('billable_id', (string) $billable->id())
                ->where('feature_key', $featureKey->value())
                ->where('window_start', $window->startsAt())
                ->where('window_end', $window->endsAt())
                ->lockForUpdate()
                ->first();

            if ($record) {
                $record->update(['used' => max(0, $record->used - $amount)]);
            } else {
                FeatureUsage::create([
                    'billable_type' => $billable->type(),
                    'billable_id' => (string) $billable->id(),
                    'feature_key' => $featureKey->value(),
                    'window_start' => $window->startsAt(),
                    'window_end' => $window->endsAt(),
                    'used' => 0,
                ]);
            }
        });
    }
}
