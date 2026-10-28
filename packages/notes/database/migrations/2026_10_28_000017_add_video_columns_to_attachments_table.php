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
        Schema::table(config('notes.table_prefix').'attachments', function (Blueprint $table) {
            $table->unsignedInteger('duration_ms')->nullable()->after('height');
            $table->string('poster_path')->nullable()->after('variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('notes.table_prefix').'attachments', function (Blueprint $table) {
            $table->dropColumn(['duration_ms', 'poster_path']);
        });
    }
};
