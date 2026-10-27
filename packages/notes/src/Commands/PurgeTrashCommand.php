<?php

namespace Coleus\Notes\Commands;

use Coleus\Notes\Facades\Notes;
use Illuminate\Console\Command;

class PurgeTrashCommand extends Command
{
    protected $signature = 'notes:purge-trash
        {--days= : Delete notes that have been in the trash longer than this many days (default: notes.trash_retention_days)}';

    protected $description = 'Permanently delete notes that have been in the trash for too long, for every user';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('notes.trash_retention_days', 30);

        if (! is_numeric($days) || (int) $days < 1) {
            $this->error('--days must be a whole number of at least 1.');

            return self::FAILURE;
        }

        $purged = Notes::note()->purgeTrash((int) $days);

        $this->info("Deleted {$purged} note(s) that were in the trash for more than {$days} day(s).");

        return self::SUCCESS;
    }
}
