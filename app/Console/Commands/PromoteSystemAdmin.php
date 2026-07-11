<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteSystemAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:promote {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant system administrator privileges to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error('User not found');

            return Command::FAILURE;
        }

        $user->system_admin = true;
        $user->save();

        $this->info("{$user->email} is now a system administrator.");

        return Command::SUCCESS;
    }
}
