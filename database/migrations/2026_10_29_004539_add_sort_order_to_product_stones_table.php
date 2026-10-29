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
        Schema::table('product_stones', function (Blueprint $table) {
            if (! Schema::hasColumn('product_stones', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('position');
            }

            $table->index(
                ['product_id', 'position', 'sort_order', 'id'],
                'product_stones_product_position_sort_index',
            );
            $table->dropUnique('product_stone_position_unique');
        });

        $sortOrders = [];

        DB::table('product_stones')
            ->orderBy('product_id')
            ->orderBy('position')
            ->orderBy('id')
            ->each(function (object $productStone) use (&$sortOrders): void {
                $key = $productStone->product_id.'|'.$productStone->position;
                $sortOrders[$key] = ($sortOrders[$key] ?? 0) + 1;

                DB::table('product_stones')
                    ->where('id', $productStone->id)
                    ->update(['sort_order' => $sortOrders[$key]]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stones', function (Blueprint $table) {
            $table->dropIndex('product_stones_product_position_sort_index');
            $table->dropColumn('sort_order');
            // This unique index can fail if legitimate duplicate stone groups exist.
            $table->unique(
                ['product_id', 'stone_id', 'position'],
                'product_stone_position_unique',
            );
        });
    }
};
