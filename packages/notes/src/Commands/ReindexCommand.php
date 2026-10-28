<?php

namespace Coleus\Notes\Commands;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\NoteAliases;
use Coleus\Notes\Services\NoteProperties;
use Coleus\Notes\Services\NoteTags;
use Coleus\Users\Models\Scopes\UserScope;
use Coleus\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class ReindexCommand extends Command
{
    protected $signature = 'notes:reindex';

    protected $description = "Rebuild every note's tags, aliases and properties from its text (run after upgrading)";

    /**
     * Tags, aliases and properties are normally kept up to date when a note
     * is saved. Notes written before those features existed only get them on
     * their next save; this rebuilds them all now. Each user's notes are
     * processed signed in as that user, so their tags stay theirs.
     */
    public function handle(): int
    {
        $ownerIds = Note::query()
            ->withoutGlobalScope(UserScope::class)
            ->withTrashed()
            ->join('model_has_users', fn ($join) => $join->on('model_has_users.model_id', '=', Note::query()->getModel()->getQualifiedKeyName())->where('model_has_users.model_type', Note::class))
            ->distinct()
            ->pluck('model_has_users.user_id');

        $total = 0;

        foreach (User::query()->whereKey($ownerIds)->get() as $user) {
            Auth::setUser($user);

            Note::query()->withTrashed()->chunkById(100, function ($notes) use (&$total) {
                foreach ($notes as $note) {
                    // Trashed notes keep no tags in the sidebar; restoring re-syncs them.
                    if (! $note->trashed()) {
                        NoteTags::sync($note);
                    }
                    NoteAliases::sync($note);
                    NoteProperties::sync($note);
                    $total++;
                }
            });
        }

        Auth::forgetUser();

        $this->info("Reindexed {$total} note(s).");

        return self::SUCCESS;
    }
}
