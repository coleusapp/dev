<?php

namespace Coleus\Music\Commands;

use Coleus\Music\Models\Track;
use Coleus\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GetMusicFilesCommand extends Command
{
    protected $signature = 'music:get-files
                            {extensions?* : File extensions to filter (default: mp3, flac, wav, aac, m4a, ogg)}
                            {--user= : User ID or email to assign tracks to (defaults to first user)}';

    protected $description = 'Get music files from storage and save them to the tracks table';

    public function handle(): void
    {
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('No user found. Pass a valid --user option.');

            return;
        }

        Auth::login($user);

        $extensions = $this->argument('extensions') ?: ['mp3', 'flac', 'wav', 'aac', 'm4a', 'ogg'];

        $files = collect(Storage::allFiles('music'))
            ->filter(fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $extensions));

        $created = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $track = Track::firstOrCreate(
                ['path' => $file],
                ['title' => pathinfo($file, PATHINFO_FILENAME)],
            );

            $track->wasRecentlyCreated ? $created++ : $skipped++;
        }

        $this->info("Done. Created: {$created}, Skipped: {$skipped}.");
    }

    private function resolveUser(): ?User
    {
        $input = $this->option('user');

        if (! $input) {
            return User::first();
        }

        return is_numeric($input)
            ? User::find($input)
            : User::where('email', $input)->first();
    }
}
