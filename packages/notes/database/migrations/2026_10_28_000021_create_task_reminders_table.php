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
        Schema::create(config('notes.table_prefix').'task_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('note_id');
            $table->char('item_hash', 64);
            $table->date('due_on');
            $table->timestamps();

            $table->unique(['user_id', 'note_id', 'item_hash', 'due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('notes.table_prefix').'task_reminders');
    }
};
