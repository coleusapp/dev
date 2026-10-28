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
        Schema::create(config('notes.table_prefix').'link_previews', function (Blueprint $table) {
            $table->id();
            $table->char('url_hash', 64)->unique();
            $table->text('url');
            $table->string('title')->nullable();
            $table->string('description', 400)->nullable();
            $table->text('image')->nullable();
            $table->string('site_name', 150)->nullable();
            $table->boolean('failed')->default(false);
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('notes.table_prefix').'link_previews');
    }
};
