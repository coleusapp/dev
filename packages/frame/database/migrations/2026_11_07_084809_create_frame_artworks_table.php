<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('frame.table_prefix').'artworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frame_id')
                ->constrained(config('frame.table_prefix').'frames')
                ->cascadeOnDelete();
            $table->string('source', 32);
            $table->unsignedBigInteger('source_id');
            $table->string('title');
            $table->string('artist_display')->nullable();
            $table->string('date_display')->nullable();
            $table->string('path');
            $table->timestamp('last_shown_at')->nullable();
            $table->timestamps();

            $table->unique(['frame_id', 'source', 'source_id']);
            $table->index(['frame_id', 'last_shown_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('frame.table_prefix').'artworks');
    }
};
