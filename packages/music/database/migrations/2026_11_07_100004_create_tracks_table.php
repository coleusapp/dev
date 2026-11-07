<?php

use Coleus\Music\Models\Album;
use Coleus\Music\Models\Artist;
use Coleus\Music\Models\Genre;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('music.table_prefix').'tracks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('path')->nullable()->unique();
            $table->foreignIdFor(Artist::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Album::class)->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Genre::class)->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedSmallInteger('track_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('music.table_prefix').'tracks');
    }
};
