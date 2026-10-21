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
        Schema::create('product_stones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('stone_id')
                ->constrained('stones')
                ->restrictOnDelete();

            $table->string('position'); // central | lateral

            $table->string('shape')->nullable();

            $table->decimal('carats', 6, 2)->nullable();
            $table->unsignedInteger('quantity')->default(1);

            $table->string('origin')->nullable();
            $table->string('certification')->nullable();
            $table->string('color')->nullable();
            $table->string('clarity')->nullable();
            $table->string('cut')->nullable();

            $table->timestamps();

            $table->unique(
                ['product_id', 'stone_id', 'position'],
                'product_stone_position_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stones');
    }
};
