<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;

class AppVersionBumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version:bump
                            {type=patch : Increment type: patch (default), minor, or major}
                            {--set= : Explicitly set a semantic version (e.g. 2.4.7)}
                            {--dry-run : Preview version bump without writing changes}
                            {--ci : Run in GitHub Actions CI mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically increment the application semantic version and synchronize all manifest files';

    /**
     * Execute the console command.
     */
    public function handle(VersionService $versionService): int
    {
        $type = strtolower(trim((string)$this->argument('type')));
        $explicit = $this->option('set');
        $isDryRun = (bool)$this->option('dry-run');
        $isCi = (bool)$this->option('ci');

        $scriptPath = base_path('scripts/version-bump.php');
        $cmd = [PHP_BINARY, $scriptPath, "--type={$type}"];
        if (!empty($explicit)) {
            $cmd[] = "--set={$explicit}";
        }
        if ($isDryRun) {
            $cmd[] = "--dry-run";
        }
        if ($isCi) {
            $cmd[] = "--ci";
        }

        // Execute the single source of truth version bump script
        $output = [];
        $returnVar = 0;
        exec(implode(' ', array_map('escapeshellarg', $cmd)), $output, $returnVar);

        $this->line(implode("\n", $output));

        if ($returnVar !== 0) {
            $this->error("Version bump script failed with exit code {$returnVar}.");
            return $returnVar;
        }

        if (!$isDryRun) {
            $vData = @json_decode(@file_get_contents(base_path('version.json')), true);
            $newVer = !empty($vData['version']) ? (string)$vData['version'] : $versionService->getLatestVersion();

            try {
                \App\Models\Setting::set('latest_version', $newVer);
                \App\Models\Setting::set('system_version', $newVer);
            } catch (\Throwable $e) {}

            $versionService->refresh();

            try {
                \Illuminate\Support\Facades\Artisan::call('view:clear');
            } catch (\Throwable $e) {}

            $this->info("✓ Application version synchronized to v{$newVer} across database and manifests.");
        }

        return 0;
    }
}
