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
        Schema::table(config('notes.table_prefix').'folders', function (Blueprint $table) {
            $table->string('sort', 20)->nullable()->after('name');
            $table->string('sort_direction', 4)->nullable()->after('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('notes.table_prefix').'folders', function (Blueprint $table) {
            $table->dropColumn(['sort', 'sort_direction']);
        });
    }
};
