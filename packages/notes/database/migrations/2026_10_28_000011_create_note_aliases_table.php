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

        Schema::create($prefix.'note_aliases', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('note_id')->constrained($prefix.'notes')->cascadeOnDelete();
            $table->string('alias');
            $table->string('alias_lower')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('notes.table_prefix').'note_aliases');
    }
};
