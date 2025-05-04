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
        Schema::table('background_jobs', function (Blueprint $table) {
            $table->integer('priority')->default(10)->after('next_retry_at'); // Add priority column with default value
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('background_jobs', function (Blueprint $table) {
            $table->dropColumn('priority'); // Remove priority column
        });
    }
};
