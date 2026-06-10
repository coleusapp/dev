<?php

use Coleus\Music\Models\Playlist;
use Coleus\Music\Models\Track;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('music.table_prefix').'playlist_track', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Playlist::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Track::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('music.table_prefix').'playlist_track');
    }
};
