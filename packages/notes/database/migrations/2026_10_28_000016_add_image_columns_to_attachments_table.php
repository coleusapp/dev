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
            $table->unsignedInteger('width')->nullable()->after('size');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->json('variants')->nullable()->after('path');
            $table->timestamp('processed_at')->nullable()->after('variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('notes.table_prefix').'attachments', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'variants', 'processed_at']);
        });
    }
};
