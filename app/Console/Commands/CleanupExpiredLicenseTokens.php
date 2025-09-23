<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class CleanupExpiredLicenseTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired license tokens by marking them as revoked';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to cleanup expired license tokens?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Starting license token cleanup...');

        try {
            $cleanedCount = $licenseService->cleanupExpiredTokens();

            $this->info("Successfully cleaned up {$cleanedCount} expired license tokens.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to cleanup expired tokens: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
