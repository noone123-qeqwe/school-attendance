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
            'installed_version'  => $metadata['installed_version'],
            'latest_version'     => $metadata['latest_version'],
            'is_up_to_date'      => $metadata['is_up_to_date'],
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
        ]);
    }
}
