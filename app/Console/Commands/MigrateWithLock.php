<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Runs database migrations guarded by a PostgreSQL advisory lock, so that when
 * several app replicas start at once only one runs the migrations and the
 * others wait. Unlike `migrate --isolated`, the advisory lock needs no tables,
 * so it also works on a brand-new (empty) database.
 */
class MigrateWithLock extends Command
{
    protected $signature = 'app:migrate';

    protected $description = 'Run migrations guarded by a cross-process lock (safe for multiple app replicas).';

    /**
     * Arbitrary, application-wide constant shared by every replica.
     */
    private const LOCK_KEY = 5107271;

    public function handle(): int
    {
        // Advisory locks are PostgreSQL-specific; on other drivers just migrate.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return Artisan::call('migrate', ['--force' => true], $this->output);
        }

        $this->info('Acquiring migration advisory lock…');
        DB::statement('SELECT pg_advisory_lock(?)', [self::LOCK_KEY]);

        try {
            return Artisan::call('migrate', ['--force' => true], $this->output);
        } finally {
            DB::statement('SELECT pg_advisory_unlock(?)', [self::LOCK_KEY]);
            $this->info('Released migration advisory lock.');
        }
    }
}
