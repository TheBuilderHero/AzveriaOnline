<?php

namespace App\Console\Commands;

use App\Services\AccountService;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class CreateAzveriaAccount extends Command
{
    protected $signature = 'azveria:account-create
        {name : Display name for the account}
        {email : Login email address}
        {password? : Password to assign. Defaults to the configured temporary password}
        {--role=player : player or admin}
        {--with-nation : Force nation creation even for non-player roles}
        {--no-force-password-reset : Do not require a password change on next login}';

    protected $description = 'Create an Azveria account from the command line';

    public function handle(AccountService $accounts): int
    {
        $role = strtolower((string) $this->option('role'));
        $password = (string) ($this->argument('password') ?: ($accounts->getNewAccountDefaults()['default_temp_password'] ?? 'password123'));

        $validator = Validator::make([
            'name' => (string) $this->argument('name'),
            'email' => (string) $this->argument('email'),
            'password' => $password,
            'role' => $role,
        ], [
            'name' => ['required', 'string', 'min:3', 'max:120', "regex:/^[A-Za-z0-9][A-Za-z0-9 _'\\-]*$/"],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'role' => ['required', 'in:admin,player'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = $accounts->createAccount([
            'name' => (string) $this->argument('name'),
            'email' => (string) $this->argument('email'),
            'password' => $password,
            'role' => $role,
            'create_nation' => $this->option('with-nation') || $role === 'player',
            'force_password_reset' => !$this->option('no-force-password-reset'),
        ]);

        $this->info('Account created successfully.');
        $this->line('ID: ' . $user->id);
        $this->line('Role: ' . $user->role);
        $this->line('Email: ' . $user->email);

        return self::SUCCESS;
    }
}