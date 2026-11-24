<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PruneUnverifiedRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:prune-unverified {--hours=24} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete abandoned unverified registration accounts older than the specified hours.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = max(0, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $query = User::where('registration_unverified', true)
            ->where('created_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $count = $query->count();
            $this->info("Dry run: {$count} unverified registration(s) older than {$hours} hour(s) would be deleted.");

            return self::SUCCESS;
        }

        $staleUsers = $query->get();
        $deleted = 0;

        foreach ($staleUsers as $staleUser) {
            User::deleteIdPhoto($staleUser->id_photo);
            $staleUser->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} unverified registration(s) older than {$hours} hour(s).");

        return self::SUCCESS;
    }
}
