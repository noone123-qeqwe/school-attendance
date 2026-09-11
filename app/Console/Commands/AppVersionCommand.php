<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;

class AppVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version {--json : Output version metadata as raw JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the centralized application release version, build, and telemetry';

    /**
     * Execute the console command.
     */
    public function handle(VersionService $versionService): int
    {
        $meta = $versionService->getFullMetadata();

        if ($this->option('json')) {
            $this->line(json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->newLine();
        $this->info("  " . $meta['name']);
        $this->line("  ────────────────────────────────────────────────────────");

        $rows = [
            ['Release Version', $meta['version_tag'] . " ({$meta['version']})"],
            ['Build ID',        $meta['build']],
            ['Git Commit',      $meta['commit']],
            ['Release Date',    $meta['release_date_formatted'] . " ({$meta['release_date']})"],
            ['Environment',     $meta['environment']],
            ['Channel',         $meta['channel']],
            ['Database Version', $meta['database_migrations_count'] . " migrations applied"],
            ['Installed Status', $meta['is_up_to_date'] ? 'Up to date ✓' : 'Update pending (installed: ' . $meta['installed_version'] . ')'],
        ];

        $this->table(['Property', 'Value'], $rows);
        $this->newLine();

        return 0;
    }
}
