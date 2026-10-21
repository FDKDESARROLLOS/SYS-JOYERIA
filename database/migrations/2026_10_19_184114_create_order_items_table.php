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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            $table->string('product_name_snapshot');
            $table->string('sku_snapshot');
            $table->string('variant_label_snapshot')->nullable();

            $table->string('selected_material')->nullable();
            $table->unsignedSmallInteger('selected_karat')->nullable();
            $table->string('selected_metal_color')->nullable();
            $table->string('selected_stone_name')->nullable();
            $table->decimal('selected_stone_carats', 6, 2)->nullable();
            $table->string('selected_size_name')->nullable();

            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
