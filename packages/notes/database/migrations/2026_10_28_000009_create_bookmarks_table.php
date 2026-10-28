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

        Schema::create($prefix.'bookmarks', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('type', 20);
            $table->foreignId('note_id')->nullable()->constrained($prefix.'notes')->cascadeOnDelete();
            $table->string('heading')->nullable();
            $table->json('filters')->nullable();
            $table->string('label', 100)->nullable();
            $table->string('group', 50)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['group', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('notes.table_prefix').'bookmarks');
    }
};
