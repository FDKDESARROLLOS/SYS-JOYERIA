<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table): void {
            $table->unsignedInteger('reserved_stock')->default(0)->after('current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table): void {
            $table->dropColumn('reserved_stock');
        });
    }
};
