<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    protected VersionService $versionService;

    public function __construct(VersionService $versionService)
    {
        $this->versionService = $versionService;
    }

    /**
     * Get centralized application version, build, and compatibility metadata.
     */
    public function index(): JsonResponse
    {
        $metadata = $this->versionService->getFullMetadata();

        return response()->json([
            'success'            => true,
            'name'               => $metadata['name'],
            'version'            => $metadata['version'],
            'version_tag'        => $metadata['version_tag'],
            'version_display'    => $metadata['version_display'],
            'build'              => $metadata['build'],
            'commit'             => $metadata['commit'],
            'release_date'       => $metadata['release_date'],
            'releaseDate'        => $metadata['release_date'],
            'environment'        => $metadata['environment'],
            'database_version'   => $metadata['database_migrations_count'],
            'current_version'    => $metadata['installed_version'],
            'installed_version'  => $metadata['installed_version'],
            'latest_version'     => $metadata['latest_version'],
            'is_up_to_date'      => $metadata['is_up_to_date'],
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Apply/install update to the specified or latest version.
     */
    public function update(\Illuminate\Http\Request $request): JsonResponse
    {
        $targetVer = $request->input('version');
        $newInstalled = $this->versionService->installUpdate($targetVer);

        return response()->json([
            'success'           => true,
            'current_version'   => $newInstalled,
            'installed_version' => $newInstalled,
            'latest_version'    => $this->versionService->getLatestVersion(),
            'is_up_to_date'     => $this->versionService->isUpToDate(),
            'message'           => "Application successfully updated to Version {$newInstalled}."
        ]);
    }

    /**
     * Publish a new release version (e.g. Version 2 -> Version 3).
     */
    public function release(\Illuminate\Http\Request $request): JsonResponse
    {
        $explicitVer = $request->input('version');
        $type = $request->input('type', 'patch');
        $installImmediately = (bool)$request->input('install', false);

        $release = $this->versionService->createRelease($type, $explicitVer, $installImmediately);

        return response()->json([
            'success'           => true,
            'latest_version'    => $release['version'],
            'current_version'   => $this->versionService->getInstalledVersion(),
            'installed_version' => $this->versionService->getInstalledVersion(),
            'is_up_to_date'     => $this->versionService->isUpToDate(),
            'release'           => $release,
            'message'           => "Release Version {$release['version']} published successfully."
        ]);
    }
}
