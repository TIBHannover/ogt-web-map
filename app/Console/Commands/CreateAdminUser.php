<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-user
                            {--email= : Email address of the user}
                            {--name= : Name of the user}
                            {--password= : Password for the user (optional, will prompt if not provided)}
                            {--promote= : Email of existing user to promote to admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user or promote an existing user to admin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('promote')) {
            return $this->promoteUser();
        } else {
            return $this->createUser();
        }
    }

    /**
     * Create a new admin user
     *
     * @return int
     */
    private function createUser()
    {
        // Get input from options or prompt
        $email = $this->option('email');
        if (!$email) {
            $email = $this->ask('Enter email address');
        }

        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter name');
        }

        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Enter password (input will be hidden)');
            $passwordConfirmation = $this->secret('Confirm password');

            if ($password !== $passwordConfirmation) {
                $this->error('Passwords do not match!');
                return 1;
            }
        }

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true, // Set as admin
        ]);

        $this->info("Admin user {$user->name} created successfully!");
        return 0;
    }

    /**
     * Promote an existing user to admin
     *
     * @return int
     */
    private function promoteUser()
    {
        $email = $this->option('promote');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        if ($user->is_admin) {
            $this->info("User {$user->name} is already an admin.");
            return 0;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("User {$user->name} has been promoted to admin!");
        return 0;
    }
}
