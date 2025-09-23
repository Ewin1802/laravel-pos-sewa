<?php

namespace App\Console\Commands;

use App\Services\TrialService;
use Illuminate\Console\Command;

class CleanupExpiredTrials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:expired-trials {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired trial subscriptions and revoke their license tokens';

    public function __construct(private TrialService $trialService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        // Find expired trials
        $expiredTrials = $this->trialService->findExpiredTrials();

        if ($expiredTrials->isEmpty()) {
            $this->info('No expired trials found.');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d expired trial(s).', $expiredTrials->count()));

        // Show details of expired trials
        $headers = ['ID', 'Merchant', 'Plan', 'Trial Started', 'Trial Ended', 'Status'];
        $rows = [];

        foreach ($expiredTrials as $trial) {
            $rows[] = [
                $trial->id,
                $trial->merchant->name ?? 'N/A',
                $trial->plan->name ?? 'No Plan',
                $trial->trial_started_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $trial->trial_end_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $trial->status,
            ];
        }

        $this->table($headers, $rows);

        if ($isDryRun) {
            $this->warn('Dry-run mode: No trials were expired.');
            return Command::SUCCESS;
        }

        if (!$this->confirm('Do you want to expire these trials?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Bulk expire trials
        $this->info('Expiring trials...');
        $expired = $this->trialService->bulkExpireTrials();

        $this->info(sprintf('Successfully expired %d trial subscription(s).', $expired));

        if ($expired < $expiredTrials->count()) {
            $failed = $expiredTrials->count() - $expired;
            $this->warn(sprintf('%d trial(s) failed to expire. Check logs for details.', $failed));
        }

        return Command::SUCCESS;
    }
}
