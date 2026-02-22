<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feature_usages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('feature_key');
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->bigInteger('used')->unsigned();
            $table->timestamps();
            $table->unique(['subscription_id', 'feature_key', 'window_start', 'window_end'], 'unique_feature_usage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usages');
    }
};
