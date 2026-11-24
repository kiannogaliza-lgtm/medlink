<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MoveIdPhotosPrivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'id-photos:move-private {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move resident government ID photos from public storage to private local storage.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');

        $moved = 0;
        $alreadyPrivate = 0;
        $missing = 0;
        $failed = 0;

        $users = User::whereNotNull('id_photo')
            ->where('id_photo', '!=', '')
            ->get(['id', 'id_photo']);

        foreach ($users as $user) {
            $path = (string) $user->id_photo;

            if ($localDisk->exists($path)) {
                $alreadyPrivate++;
                continue;
            }

            if (!$publicDisk->exists($path)) {
                $missing++;
                continue;
            }

            if ($dryRun) {
                $moved++;
                continue;
            }

            try {
                $contents = $publicDisk->get($path);
                $expectedSize = $publicDisk->size($path);

                $written = $localDisk->put($path, $contents);

                if ($written && $localDisk->exists($path) && $localDisk->size($path) === $expectedSize) {
                    $publicDisk->delete($path);
                    $moved++;
                } else {
                    $failed++;
                }
            } catch (\Throwable) {
                $failed++;
            }
        }

        if ($dryRun) {
            $this->info("Dry run: would_move={$moved}, already_private={$alreadyPrivate}, missing={$missing}, failed={$failed}");
        } else {
            $this->info("Completed: moved={$moved}, already_private={$alreadyPrivate}, missing={$missing}, failed={$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
