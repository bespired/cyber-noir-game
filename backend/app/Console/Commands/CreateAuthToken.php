<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;

class CreateAuthToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-token {userId=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Bearer token for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $token = $user->createToken('auth-token-postman')->plainTextToken;

        $this->info("Token created for user: {$user->name} ({$user->email})");
        $this->line("Bearer Token:");
        $this->line($token);

        return 0;
    }
}
