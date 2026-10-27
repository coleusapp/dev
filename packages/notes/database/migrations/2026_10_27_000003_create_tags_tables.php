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

        Schema::create($prefix.'tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->timestamps();
        });

        Schema::create($prefix.'note_tag', function (Blueprint $table) use ($prefix) {
            $table->foreignId('note_id')->constrained($prefix.'notes')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained($prefix.'tags')->cascadeOnDelete();
            $table->primary(['note_id', 'tag_id']);
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('notes.table_prefix');

        Schema::dropIfExists($prefix.'note_tag');
        Schema::dropIfExists($prefix.'tags');
    }
};
