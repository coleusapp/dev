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
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mariadb', 'mysql'], true);

        Schema::table(config('notes.table_prefix').'notes', function (Blueprint $table) use ($supportsFullText) {
            $table->string('title')->default('')->after('id');
            $table->timestamp('pinned_at')->nullable()->after('body');
            $table->timestamp('archived_at')->nullable()->after('pinned_at');
            $table->softDeletes();

            $table->index('title');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('pinned_at');
            $table->index('archived_at');
            $table->index('deleted_at');

            if ($supportsFullText) {
                $table->fullText(['title', 'body']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $supportsFullText = in_array(Schema::getConnection()->getDriverName(), ['mariadb', 'mysql'], true);

        Schema::table(config('notes.table_prefix').'notes', function (Blueprint $table) use ($supportsFullText) {
            if ($supportsFullText) {
                $table->dropFullText(['title', 'body']);
            }

            $table->dropIndex(['title']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['pinned_at']);
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['title', 'pinned_at', 'archived_at']);
        });
    }
};
