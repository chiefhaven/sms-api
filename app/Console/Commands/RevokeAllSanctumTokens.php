<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use User;
use Laravel\Sanctum\PersonalAccessToken; // Import the PersonalAccessToken model

class RevokeAllSanctumTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:revoke-all-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revokes all Laravel Sanctum API tokens for all users.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Revoking all Sanctum API tokens for all users...');

        // Delete all personal access tokens
        PersonalAccessToken::query()->delete();

        $this->info('All Sanctum API tokens have been revoked.');
    }
}