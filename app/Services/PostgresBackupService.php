<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class PostgresBackupService
{
    public function dumpTo(string $filepath): void
    {
        $connection = $this->connectionDetails();

        $this->runProcess(new Process([
            'pg_dump',
            '--host', $connection['host'],
            '--port', $connection['port'],
            '--username', $connection['username'],
            '--dbname', $connection['database'],
            '--no-password',
            '--no-owner',
            '--no-privileges',
            '--clean',
            '--if-exists',
            '--format=plain',
            '--file', $filepath,
        ], null, $this->processEnvironment($connection), null, $this->timeout()));
    }

    public function restoreFrom(string $filepath): void
    {
        $connection = $this->connectionDetails();

        $this->runProcess(new Process([
            'psql',
            '--host', $connection['host'],
            '--port', $connection['port'],
            '--username', $connection['username'],
            '--dbname', $connection['database'],
            '--no-password',
            '--set=ON_ERROR_STOP=1',
            '--single-transaction',
            '--file', $filepath,
        ], null, $this->processEnvironment($connection), null, $this->timeout()));
    }

    /** @return array{host: string, port: string, database: string, username: string, password: string, sslmode: string} */
    protected function connectionDetails(): array
    {
        $connection = config('database.connections.pgsql', []);
        $url = $connection['url'] ?? null;

        if (is_string($url) && $url !== '') {
            $parts = parse_url($url);
            if (!is_array($parts) || empty($parts['host']) || empty($parts['path'])) {
                throw new RuntimeException('PostgreSQL backup connection URL is invalid.');
            }

            parse_str($parts['query'] ?? '', $query);
            $connection = array_merge($connection, [
                'host' => $parts['host'],
                'port' => $parts['port'] ?? 5432,
                'database' => ltrim(rawurldecode($parts['path']), '/'),
                'username' => rawurldecode($parts['user'] ?? ''),
                'password' => rawurldecode($parts['pass'] ?? ''),
                'sslmode' => $query['sslmode'] ?? ($connection['sslmode'] ?? 'prefer'),
            ]);
        }

        $details = [
            'host' => (string) ($connection['host'] ?? 'localhost'),
            'port' => (string) ($connection['port'] ?? 5432),
            'database' => (string) ($connection['database'] ?? ''),
            'username' => (string) ($connection['username'] ?? ''),
            'password' => (string) ($connection['password'] ?? ''),
            'sslmode' => (string) ($connection['sslmode'] ?? ''),
        ];

        if ($details['database'] === '' || $details['username'] === '') {
            throw new RuntimeException('PostgreSQL backup connection is incomplete.');
        }

        if (!ctype_digit($details['port']) || (int) $details['port'] < 1 || (int) $details['port'] > 65535) {
            throw new RuntimeException('PostgreSQL backup connection port is invalid.');
        }

        return $details;
    }

    /** @param array{password: string, sslmode: string} $connection */
    protected function processEnvironment(array $connection): array
    {
        $environment = [];

        if ($connection['password'] !== '') {
            $environment['PGPASSWORD'] = $connection['password'];
        }

        if ($connection['sslmode'] !== '') {
            $environment['PGSSLMODE'] = $connection['sslmode'];
        }

        return $environment;
    }

    protected function runProcess(Process $process): void
    {
        try {
            $process->mustRun();
        } catch (\Throwable $exception) {
            // Do not surface process output: it can contain database names or SQL details.
            throw new RuntimeException('PostgreSQL backup process failed.', 0, $exception);
        }
    }

    protected function timeout(): int
    {
        return max(30, (int) config('services.backup.process_timeout', 300));
    }
}
