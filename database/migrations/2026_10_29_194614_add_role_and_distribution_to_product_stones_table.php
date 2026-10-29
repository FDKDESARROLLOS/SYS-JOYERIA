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
            $table->string('position')->nullable()->change();
            $table->string('role')->nullable()->after('position');
            $table->string('distribution')->nullable()->after('role');
        });

        DB::table('product_stones')
            ->where('position', 'central')
            ->update([
                'role' => 'main',
                'distribution' => 'single',
            ]);

        DB::table('product_stones')
            ->where('position', 'lateral')
            ->update([
                'role' => 'accent',
                'distribution' => 'symmetric',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stones', function (Blueprint $table) {
            $table->dropColumn(['role', 'distribution']);
        });
    }
};
