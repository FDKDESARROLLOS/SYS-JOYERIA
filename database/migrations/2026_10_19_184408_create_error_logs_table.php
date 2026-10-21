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
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('error_code_id')
                ->nullable()
                ->constrained('error_codes')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('source');
            $table->string('module')->nullable();
            $table->string('action')->nullable();

            $table->text('message');

            $table->string('status')->default('pending');

            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->string('route')->nullable();

            $table->string('exception_type')->nullable();
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();

            $table->json('context')->nullable();

            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
