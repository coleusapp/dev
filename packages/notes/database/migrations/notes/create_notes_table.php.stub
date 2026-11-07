<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('notes.table_prefix').'notes', function (Blueprint $table) {
            $table->id();
            $table->longText('body')->nullable();
            $table->timestamp('pinned_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->index('updated_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notes.table_prefix').'notes');
    }
};
