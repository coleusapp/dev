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
        $prefix = config('notes.table_prefix');

        Schema::create($prefix.'folders', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained($prefix.'folders')->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::table($prefix.'notes', function (Blueprint $table) use ($prefix) {
            $table->foreignId('folder_id')->nullable()->after('id')->constrained($prefix.'folders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('notes.table_prefix');

        Schema::table($prefix.'notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });

        Schema::dropIfExists($prefix.'folders');
    }
};
