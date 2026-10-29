<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
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
            $table->unsignedInteger('stones_per_side')
                ->nullable()
                ->after('quantity');
        });

        /**
         * Only even lateral totals can be safely interpreted as symmetric groups.
         * Odd legacy totals intentionally remain null and their quantity is preserved.
         */
        DB::table('product_stones')
            ->where('position', 'lateral')
            ->where('quantity', '>=', 2)
            ->whereRaw('MOD(quantity, 2) = 0')
            ->orderBy('id')
            ->chunkById(100, function (Collection $productStones): void {
                foreach ($productStones as $productStone) {
                    DB::table('product_stones')
                        ->where('id', $productStone->id)
                        ->update([
                            'stones_per_side' => intdiv((int) $productStone->quantity, 2),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stones', function (Blueprint $table) {
            $table->dropColumn('stones_per_side');
        });
    }
};
