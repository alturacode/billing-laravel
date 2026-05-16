<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Common\UsageWindow;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageLedger;
use Illuminate\Support\Facades\DB;

final readonly class EloquentUsageLedger implements UsageLedger
{
    public function record(UsageEvent $event): bool
    {
        return DB::table('feature_usage_events')->insertOrIgnore([
            'id' => $event->id()->value(),
            'billable_type' => $event->billable()->type(),
            'billable_id' => (string) $event->billable()->id(),
            'feature_key' => $event->featureKey()->value(),
            'amount' => $event->amount(),
            'recorded_at' => $event->recordedAt(),
            'metadata' => json_encode($event->metadata(), JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    public function getUsedAmount(BillableIdentity $billable, FeatureKey $featureKey, UsageWindow $window): int
    {
        return (int) FeatureUsageEvent::query()
            ->where('billable_type', $billable->type())
            ->where('billable_id', (string) $billable->id())
            ->where('feature_key', $featureKey->value())
            ->where('recorded_at', '>=', $window->startsAt())
            ->where('recorded_at', '<', $window->endsAt())
            ->sum('amount');
    }
}
