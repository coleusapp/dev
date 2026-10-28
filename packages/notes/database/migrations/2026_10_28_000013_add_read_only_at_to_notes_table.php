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
        Schema::table(config('notes.table_prefix').'notes', function (Blueprint $table) {
            $table->timestamp('read_only_at')->nullable()->after('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('notes.table_prefix').'notes', function (Blueprint $table) {
            $table->dropColumn('read_only_at');
        });
    }
};
