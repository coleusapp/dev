<?php

use Coleus\Music\Models\Genre;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('music.table_prefix').'genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Genre::class)->constrained()->cascadeOnUpdate()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('music.table_prefix').'genres');
    }
};
