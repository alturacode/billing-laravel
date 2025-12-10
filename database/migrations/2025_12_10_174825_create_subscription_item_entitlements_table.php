<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_item_entitlements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('subscription_item_id')->constrained('subscription_items')->cascadeOnDelete();
            $table->string('feature_key');
            $table->string('feature_value_kind');
            $table->string('feature_value_string')->nullable();
            $table->integer('feature_value_integer')->nullable();
            $table->boolean('feature_value_boolean')->nullable();
            $table->timestamp('effective_window_starts_at')->nullable();
            $table->timestamp('effective_window_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_item_entitlements');
    }
};
