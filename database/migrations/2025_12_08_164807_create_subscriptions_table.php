<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->morphs('billable'); // billable_type, billable_id
            $table->string('provider', 35);
            $table->string('name', 35);
            $table->string('status'); // cast to SubscriptionStatus enum
            $table->ulid('primary_item_id')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
