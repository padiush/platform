<?php

namespace App\Console\Commands;

use App\Models\AdminAction;
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

        // From the console: no administrator in the panel did this.
        AdminAction::record(null, AdminAction::ADMIN_PROMOTED, ['name' => $user->name, 'email' => $user->email]);

        $this->info("{$user->email} is now a system administrator.");

        return Command::SUCCESS;
    }
}
