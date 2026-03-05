<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('feature_usages', function (Blueprint $table) {
            $table->string('billable_type')->nullable()->after('id');
            $table->string('billable_id')->nullable()->after('billable_type');
        });

        DB::table('feature_usages')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $subscription = DB::table('subscriptions')
                        ->select('billable_type', 'billable_id')
                        ->where('id', $row->subscription_id)
                        ->first();

                    if (!$subscription) {
                        continue;
                    }

                    DB::table('feature_usages')
                        ->where('id', $row->id)
                        ->update([
                            'billable_type' => $subscription->billable_type,
                            'billable_id' => $subscription->billable_id,
                        ]);
                }
            });

        DB::table('feature_usages')
            ->select('billable_type', 'billable_id', 'feature_key')
            ->whereNotNull('billable_type')
            ->whereNotNull('billable_id')
            ->distinct()
            ->orderBy('billable_type')
            ->orderBy('billable_id')
            ->orderBy('feature_key')
            ->chunk(100, function ($groups) {
                foreach ($groups as $group) {
                    $records = DB::table('feature_usages')
                        ->where('billable_type', $group->billable_type)
                        ->where('billable_id', $group->billable_id)
                        ->where('feature_key', $group->feature_key)
                        ->orderByDesc('window_end')
                        ->orderByDesc('window_start')
                        ->orderByDesc('created_at')
                        ->get();

                    if ($records->count() <= 1) {
                        continue;
                    }

                    $latest = $records->first();
                    $totalUsed = (int) $records->sum('used');

                    DB::table('feature_usages')
                        ->where('id', $latest->id)
                        ->update(['used' => $totalUsed]);

                    DB::table('feature_usages')
                        ->whereIn('id', $records->pluck('id')->slice(1)->all())
                        ->delete();
                }
            });

        Schema::table('feature_usages', function (Blueprint $table) {
            $table->dropUnique('unique_feature_usage');
            $table->dropForeign(['subscription_id']);
            $table->dropColumn('subscription_id');
            $table->unique(['billable_type', 'billable_id', 'feature_key', 'window_start', 'window_end'], 'unique_feature_usage');
        });
    }

    public function down(): void
    {
        Schema::table('feature_usages', function (Blueprint $table) {
            $table->ulid('subscription_id')->nullable()->after('id');
        });

        DB::table('feature_usages')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $subscription = DB::table('subscriptions')
                        ->select('id')
                        ->where('billable_type', $row->billable_type)
                        ->where('billable_id', $row->billable_id)
                        ->orderByDesc('created_at')
                        ->first();

                    if (!$subscription) {
                        continue;
                    }

                    DB::table('feature_usages')
                        ->where('id', $row->id)
                        ->update([
                            'subscription_id' => $subscription->id,
                        ]);
                }
            });

        DB::table('feature_usages')
            ->select('subscription_id', 'feature_key')
            ->whereNotNull('subscription_id')
            ->distinct()
            ->orderBy('subscription_id')
            ->orderBy('feature_key')
            ->chunk(100, function ($groups) {
                foreach ($groups as $group) {
                    $records = DB::table('feature_usages')
                        ->where('subscription_id', $group->subscription_id)
                        ->where('feature_key', $group->feature_key)
                        ->orderByDesc('window_end')
                        ->orderByDesc('window_start')
                        ->orderByDesc('created_at')
                        ->get();

                    if ($records->count() <= 1) {
                        continue;
                    }

                    $latest = $records->first();
                    $totalUsed = (int) $records->sum('used');

                    DB::table('feature_usages')
                        ->where('id', $latest->id)
                        ->update(['used' => $totalUsed]);

                    DB::table('feature_usages')
                        ->whereIn('id', $records->pluck('id')->slice(1)->all())
                        ->delete();
                }
            });

        Schema::table('feature_usages', function (Blueprint $table) {
            $table->dropUnique('unique_feature_usage');
            $table->unique(['subscription_id', 'feature_key', 'window_start', 'window_end'], 'unique_feature_usage');
            $table->dropColumn(['billable_type', 'billable_id']);
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
        });
    }
};
