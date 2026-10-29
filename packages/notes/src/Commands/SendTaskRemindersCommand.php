<?php

namespace Coleus\Notes\Commands;

use Coleus\Notes\Models\Note;
use Coleus\Notes\Notifications\TasksDueNotification;
use Coleus\Notes\Services\TaskList;
use Coleus\Users\Models\Scopes\UserScope;
use Coleus\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reminds each user of open checklist items due soon (O9.8): due within
 * notes.reminders.days_before days, or already overdue, in notes that
 * aren't trashed or locked. Each item is mentioned once per due date
 * (notes_task_reminders), so running it more often only catches new ones.
 */
class SendTaskRemindersCommand extends Command
{
    protected $signature = 'notes:send-reminders';

    protected $description = 'Remind users of checklist items in their notes that are due soon';

    public function handle(): int
    {
        if (! config('notes.reminders.enabled', true)) {
            return self::SUCCESS;
        }

        $until = Carbon::today()->addDays((int) config('notes.reminders.days_before', 1))->toDateString();
        $table = config('notes.table_prefix').'task_reminders';
        $ownerIds = Note::query()
            ->withoutGlobalScope(UserScope::class)
            ->join('model_has_users', fn ($join) => $join->on('model_has_users.model_id', '=', Note::query()->getModel()->getQualifiedKeyName())->where('model_has_users.model_type', Note::class))
            ->where('body', 'like', '%[ ]%@%')
            ->distinct()
            ->pluck('model_has_users.user_id');
        $sent = 0;

        foreach (User::query()->whereKey($ownerIds)->get() as $user) {
            Auth::setUser($user);
            $due = [];

            foreach (app(TaskList::class)->grouped(['state' => 'due']) as $group) {
                foreach ($group['items'] as $item) {
                    $hash = hash('sha256', $item['text']);

                    if ($item['due'] > $until || DB::table($table)->where(['user_id' => $user->id, 'note_id' => $group['note']['id'], 'item_hash' => $hash, 'due_on' => $item['due']])->exists()) {
                        continue;
                    }

                    $due[] = ['note_id' => $group['note']['id'], 'note_title' => $group['note']['title'], 'text' => $item['text'], 'due' => $item['due'], 'hash' => $hash];
                }
            }

            if ($due === []) {
                continue;
            }

            usort($due, fn (array $a, array $b) => $a['due'] <=> $b['due']);
            $user->notify(new TasksDueNotification(array_map(fn (array $item) => array_diff_key($item, ['hash' => 1]), $due)));
            DB::table($table)->insert(array_map(fn (array $item) => ['user_id' => $user->id, 'note_id' => $item['note_id'], 'item_hash' => $item['hash'], 'due_on' => $item['due'], 'created_at' => now(), 'updated_at' => now()], $due));
            $sent++;
        }

        Auth::forgetUser();

        $this->info("Sent reminders to {$sent} user(s).");

        return self::SUCCESS;
    }
}
