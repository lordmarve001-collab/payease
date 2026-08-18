<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PayeaseBackupDatabase extends Command
{
    protected $signature = 'payease:backup-database
                            {--disk= : Filesystem disk to store the backup}
                            {--retention=7 : Days to keep backups}';

    protected $description = 'Create a MySQL/MariaDB dump and store it on the configured backup disk';

    public function handle(): int
    {
        $disk = $this->option('disk') ?? config('backup.disk', 'local');
        $retention = (int) ($this->option('retention') ?? config('backup.retention_days', 7));
        $prefix = (string) config('backup.prefix', 'backups/');

        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        if (blank($db) || blank($user)) {
            $this->error('Database credentials are not configured.');

            return Command::FAILURE;
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "payease_backup_{$timestamp}.sql.gz";
        $storagePath = rtrim($prefix, '/') . '/' . $filename;
        $tempPath = storage_path("app/backups/{$filename}");

        $dir = dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $optionsFile = tempnam(sys_get_temp_dir(), 'mysqldump_');
        file_put_contents($optionsFile, sprintf(
            "[mysqldump]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
            $host,
            $port,
            $user,
            $pass
        ));

        $command = sprintf(
            'mysqldump --defaults-extra-file=%s --single-transaction --routines --triggers --events %s | gzip > %s',
            escapeshellarg($optionsFile),
            escapeshellarg($db),
            escapeshellarg($tempPath)
        );

        $this->info("Dumping database '{$db}'...");

        $exitCode = $this->runDump($command, $output);

        @unlink($optionsFile);

        if ($exitCode !== 0) {
            $this->error('mysqldump failed: ' . implode("\n", $output));

            return Command::FAILURE;
        }

        $this->info("Backup saved locally at {$tempPath}");

        $stream = fopen($tempPath, 'r');
        if ($stream === false) {
            $this->error('Unable to read local backup file for upload.');

            return Command::FAILURE;
        }

        if (Storage::disk($disk)->put($storagePath, $stream)) {
            $this->info("Backup uploaded to disk [{$disk}] as {$storagePath}");
        } else {
            $this->warn("Could not upload backup to disk [{$disk}]; file remains at {$tempPath}");
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        // Clean old backups in the configured prefix only
        $cutoff = Carbon::now()->subDays($retention);
        $files = Storage::disk($disk)->files($prefix);
        $deleted = 0;

        foreach ($files as $file) {
            if (str_starts_with(basename($file), 'payease_backup_') && str_ends_with($file, '.sql.gz')) {
                $lastModified = Storage::disk($disk)->lastModified($file);
                if ($lastModified && Carbon::createFromTimestamp($lastModified)->lt($cutoff)) {
                    Storage::disk($disk)->delete($file);
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old backup(s) older than {$retention} days.");
        }

        // Remove local temp file
        @unlink($tempPath);

        $this->info('Database backup completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Execute the shell dump command.
     *
     * @param-out array<int, string> $output
     */
    protected function runDump(string $command, ?array &$output = null): int
    {
        $output = [];
        $exitCode = 0;
        exec("{$command} 2>&1", $output, $exitCode);

        return $exitCode;
    }
}
