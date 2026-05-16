<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feature_usage_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('billable_type');
            $table->string('billable_id');
            $table->string('feature_key');
            $table->unsignedBigInteger('amount');
            $table->timestamp('recorded_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['billable_type', 'billable_id', 'feature_key', 'recorded_at'], 'feature_usage_events_lookup');
        });

        if (!Schema::hasTable('feature_usages')) {
            return;
        }

        DB::table('feature_usages')
            ->where('used', '>', 0)
            ->whereNotNull('billable_type')
            ->whereNotNull('billable_id')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                $now = now();
                $events = [];

                foreach ($rows as $row) {
                    $events[] = [
                        'id' => $row->id,
                        'billable_type' => $row->billable_type,
                        'billable_id' => (string) $row->billable_id,
                        'feature_key' => $row->feature_key,
                        'amount' => (int) $row->used,
                        'recorded_at' => $row->window_start,
                        'metadata' => json_encode([
                            'source' => 'legacy_feature_usages',
                            'window_start' => $row->window_start,
                            'window_end' => $row->window_end,
                        ], JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('feature_usage_events')->insertOrIgnore($events);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usage_events');
    }
};
