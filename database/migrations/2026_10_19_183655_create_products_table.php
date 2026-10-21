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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('collection_id')
                ->nullable()
                ->constrained('collections')
                ->nullOnDelete();

            $table->foreignId('base_size_id')
                ->nullable()
                ->constrained('sizes')
                ->nullOnDelete();

            $table->foreignId('material_id')
                ->nullable()
                ->constrained('materials')
                ->nullOnDelete();

            $table->string('name');
            $table->string('model');
            $table->string('code')->unique();

            $table->string('base_sku')->nullable()->unique();
            $table->string('slug')->unique();

            $table->string('subtitle')->nullable();
            $table->text('short_description')->nullable();

            $table->unsignedSmallInteger('karat')->nullable();
            $table->string('metal_color')->nullable();

            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable();

            $table->string('status')->default('draft');

            $table->boolean('featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('bestseller')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
