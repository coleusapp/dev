<?php

namespace Coleus\Users\Console\Commands;

use Coleus\Users\Services\UserService;
use Illuminate\Console\Command;

class ManageUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:manage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage users';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $action = $this->choice(
            'Which action would you like to manage?',
            ['create', 'edit', 'delete', 'index'],
            0
        );

        if ($action === 'create') {
            app(UserService::class)->create([
                'name' => $this->ask('Name?'),
                'email' => $this->ask('Email?'),
                'password' => $this->secret('Password?'),
            ]);
            $this->info('User created successfully.');
        }
    }
}
