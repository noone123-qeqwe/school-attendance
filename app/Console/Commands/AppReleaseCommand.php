<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;

class AppReleaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:release
                            {type=patch : Increment type: patch (default), minor, or major}
                            {--set= : Specify an explicit semantic version (e.g. 2.5.0)}
                            {--title= : Optional release title}
                            {--desc= : Optional release description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new release, bump application semver, build number, and sync all client manifests';

    /**
     * Execute the console command.
     */
    public function handle(VersionService $versionService): int
    {
        $type = strtolower(trim((string)$this->argument('type')));
        $explicit = $this->option('set');

        if (!in_array($type, ['patch', 'minor', 'major', 'custom']) && empty($explicit)) {
            $this->error("Invalid release type '{$type}'. Allowed types: patch, minor, major, or use --set=<version>.");
            return 1;
        }

        $currentVer = $versionService->getVersion();
        $this->info("Current application release: v{$currentVer}");
        $this->line("Executing automated release workflow...");

        $result = $versionService->createRelease($type, $explicit);

        $this->newLine();
        $this->info("✓ Application release successfully created!");
        $this->table(
            ['Property', 'Value'],
            [
                ['Previous Version', 'v' . $result['previous_version']],
                ['New Release Version', $result['version_tag'] . " ({$result['version']})"],
                ['Build Identifier', $result['build']],
                ['Git Commit', $result['commit']],
                ['Service Worker Cache', $result['sw_version']],
                ['Release Date', $result['release_date']],
            ]
        );
        $this->newLine();
        $this->comment("All client manifests (version.json, package.json, manifest.json, sw.js) and caches have been synchronized.");

        return 0;
    }
}
