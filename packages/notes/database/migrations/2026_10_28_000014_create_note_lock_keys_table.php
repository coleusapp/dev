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
        Schema::create(config('notes.table_prefix').'lock_keys', function (Blueprint $table) {
            $table->id();
            $table->string('salt', 64);
            $table->unsignedInteger('iterations');
            $table->string('verifier_iv', 32);
            $table->text('verifier');
            $table->string('hint')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();
        });

        Schema::table(config('notes.table_prefix').'notes', function (Blueprint $table) {
            $table->unsignedBigInteger('lock_key_id')->nullable()->after('read_only_at')->index();
            $table->string('locked_iv', 32)->nullable()->after('lock_key_id');
            $table->longText('locked_body')->nullable()->after('locked_iv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('notes.table_prefix').'notes', function (Blueprint $table) {
            $table->dropIndex(['lock_key_id']);
            $table->dropColumn(['lock_key_id', 'locked_iv', 'locked_body']);
        });

        Schema::dropIfExists(config('notes.table_prefix').'lock_keys');
    }
};
