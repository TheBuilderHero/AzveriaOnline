<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Console\Command;

class DeleteAzveriaAccount extends Command
{
    protected $signature = 'azveria:account-delete
        {identifier : User ID or email address}
        {--allow-admin : Permit admin account deletion}
        {--confirm-name= : Require an exact display-name match before deletion}';

    protected $description = 'Delete an Azveria account and its owned nation data';

    public function handle(AccountService $accounts): int
    {
        $identifier = (string) $this->argument('identifier');
        $user = ctype_digit($identifier)
            ? User::find((int) $identifier)
            : User::where('email', $identifier)->first();

        if (!$user) {
            $this->error('No matching account was found.');
            return self::FAILURE;
        }

        $confirmName = (string) $this->option('confirm-name');
        if ($confirmName !== '' && $confirmName !== $user->name) {
            $this->error('The confirmation name does not match the target account.');
            return self::FAILURE;
        }

        if (!$this->confirm('Delete account "' . $user->name . '" (' . $user->email . ')? This cannot be undone.')) {
            $this->line('Deletion cancelled.');
            return self::SUCCESS;
        }

        $accounts->deleteAccount($user, (bool) $this->option('allow-admin'));
        $this->info('Account deleted.');

        return self::SUCCESS;
    }
}