<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->unsignedBigInteger('size_id')->nullable()->change();
            });

            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign('product_variants_size_id_foreign');
            $table->unsignedBigInteger('size_id')->nullable()->change();
            $table->foreign('size_id', 'product_variants_size_id_foreign')
                ->references('id')
                ->on('sizes')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('product_variants')->whereNull('size_id')->exists()) {
            throw new RuntimeException(
                'Cannot make product_variants.size_id NOT NULL while variants without a size exist.',
            );
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->unsignedBigInteger('size_id')->nullable(false)->change();
            });

            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign('product_variants_size_id_foreign');
            $table->unsignedBigInteger('size_id')->nullable(false)->change();
            $table->foreign('size_id', 'product_variants_size_id_foreign')
                ->references('id')
                ->on('sizes')
                ->restrictOnDelete();
        });
    }
};
