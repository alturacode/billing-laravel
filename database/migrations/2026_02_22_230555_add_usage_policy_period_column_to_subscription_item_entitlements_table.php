<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_item_entitlements', function (Blueprint $table) {
            $table->string('usage_policy_period')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_item_entitlements', function (Blueprint $table) {
            $table->dropColumn('usage_policy_period');
        });
    }
};
