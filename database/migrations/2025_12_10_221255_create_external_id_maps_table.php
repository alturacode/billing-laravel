<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('external_id_maps', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100);
            $table->string('provider', 75);
            $table->string('internal_id');
            $table->string('external_id');
            $table->timestamps();

            $table->unique(['type', 'provider', 'external_id']);
            $table->unique(['type', 'provider', 'internal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_id_maps');
    }
};
