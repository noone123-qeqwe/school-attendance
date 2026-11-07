<?php

namespace Tests\Unit;

use App\Services\PostgresBackupService;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PostgresBackupServiceTest extends TestCase
{
    public function test_dump_uses_pg_dump_with_schema_and_data_and_keeps_password_out_of_arguments(): void
    {
        config(['database.connections.pgsql' => [
            'url' => 'postgresql://backup-user:secret%40value@db.example.test:5444/attendance?sslmode=require',
        ]]);

        $service = new RecordingPostgresBackupService();
        $service->dumpTo(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'attendance-backup.sql');

        $process = $service->processes[0];
        $command = $process->getCommandLine();
        $this->assertStringContainsString('pg_dump', $command);
        $this->assertStringContainsString('--clean', $command);
        $this->assertStringContainsString('--if-exists', $command);
        $this->assertStringContainsString('--format=plain', $command);
        $this->assertStringContainsString('db.example.test', $command);
        $this->assertStringNotContainsString('secret', $command);
        $this->assertSame('secret@value', $process->getEnv()['PGPASSWORD']);
        $this->assertSame('require', $process->getEnv()['PGSSLMODE']);
    }

    public function test_restore_uses_psql_with_stop_on_error_and_single_transaction(): void
    {
        config(['database.connections.pgsql' => [
            'url' => 'postgres://backup-user:fake-test-password@db.example.test/attendance?sslmode=require',
        ]]);

        $service = new RecordingPostgresBackupService();
        $service->restoreFrom(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restore.sql');

        $command = $service->processes[0]->getCommandLine();
        $this->assertStringContainsString('psql', $command);
        $this->assertStringContainsString('--set=ON_ERROR_STOP=1', $command);
        $this->assertStringContainsString('--single-transaction', $command);
        $this->assertStringNotContainsString('fake-test-password', $command);
    }

    public function test_malformed_connection_details_are_rejected_before_running_process(): void
    {
        config(['database.connections.pgsql' => [
            'host' => 'db.example.test',
            'port' => '5432',
            'database' => '',
            'username' => 'backup-user',
            'password' => '',
            'sslmode' => 'require',
        ]]);

        $service = new RecordingPostgresBackupService();
        $this->expectException(\RuntimeException::class);
        $service->dumpTo(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'attendance-backup.sql');
    }
}

class RecordingPostgresBackupService extends PostgresBackupService
{
    /** @var list<Process> */
    public array $processes = [];

    protected function runProcess(Process $process): void
    {
        $this->processes[] = $process;
    }
}
