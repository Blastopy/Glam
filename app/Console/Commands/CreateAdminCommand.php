<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {name} {email} {phone} {password}';

    protected $description = 'Create an admin account for the SalonBook admin panel';

    public function handle(): int
    {
        $validator = Validator::make($this->arguments(), [
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'phone' => $this->argument('phone'),
            'password' => Hash::make($this->argument('password')),
            'role' => 'admin',
        ]);

        $this->info('Admin account created — they can now log in via the normal /login screen.');

        return self::SUCCESS;
    }
}
