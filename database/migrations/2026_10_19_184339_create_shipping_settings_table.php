<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('enabled')->default(true);

            $table->string('method_name');
            $table->string('estimated_time')->nullable();

            $table->decimal('shipping_cost', 12, 2)->default(0);

            $table->boolean('free_shipping_enabled')->default(false);

            $table->string('free_shipping_type')->nullable();
            // all | minimum

            $table->decimal('free_shipping_minimum', 12, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_settings');
    }
};
