<?php

namespace Tests\Feature;

use App\Console\Commands\PayeaseBackupDatabase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackupCommandTest extends TestCase
{
    #[Test]
    public function backup_command_fails_when_mysql_credentials_are_missing(): void
    {
        Config::set('database.connections.mysql.database', null);
        Config::set('database.connections.mysql.username', null);

        $this->artisan('payease:backup-database')
            ->expectsOutputToContain('Database credentials are not configured.')
            ->assertFailed();
    }

    #[Test]
    public function backup_command_creates_backup_and_uploads_to_disk(): void
    {
        Storage::fake('local');

        Config::set('database.connections.mysql.database', 'payease');
        Config::set('database.connections.mysql.username', 'deploy_user');
        Config::set('database.connections.mysql.password', 'secret');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', 3306);
        Config::set('backup.disk', 'local');
        Config::set('backup.retention_days', 7);
        Config::set('backup.prefix', 'backups/');

        $command = new class extends PayeaseBackupDatabase
        {
            protected function runDump(string $command, ?array &$output = null): int
            {
                if (preg_match('/gzip > ["\'](.+?)["\']/', $command, $matches)) {
                    $tempPath = $matches[1];
                    $dir = dirname($tempPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($tempPath, 'gzipped-sql-dump-content');
                }
                $output = [];

                return 0;
            }
        };

        app(Kernel::class)->registerCommand($command);

        $this->artisan('payease:backup-database')
            ->expectsOutputToContain("Dumping database 'payease'...")
            ->expectsOutputToContain('Backup uploaded to disk [local]')
            ->expectsOutputToContain('Database backup completed successfully.')
            ->assertSuccessful();

        $files = Storage::disk('local')->allFiles('backups');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('backups/payease_backup_', $files[0]);
        $this->assertStringEndsWith('.sql.gz', $files[0]);
    }

}
