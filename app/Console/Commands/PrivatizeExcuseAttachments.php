<?php

namespace App\Console\Commands;

use App\Models\ExcuseSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivatizeExcuseAttachments extends Command
{
    protected $signature = 'excuses:privatize-attachments {--apply : Copy local public attachments to private storage and remove public originals}';

    protected $description = 'Move legacy local excuse documents out of public storage';

    public function handle(): int
    {
        $privateDiskName = (string) config('filesystems.private_uploads_disk', 'local');
        if ($privateDiskName === 'public') {
            $this->error('PRIVATE_UPLOADS_DISK cannot be the public disk.');
            return self::FAILURE;
        }

        $dryRun = !$this->option('apply');
        $publicDisk = config('filesystems.disks.public.driver') === 'local'
            ? Storage::disk('public')
            : Storage::build([
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'visibility' => 'public',
            ]);
        $privateDisk = Storage::disk($privateDiskName);
        $moved = 0;
        $missing = 0;
        $external = 0;

        ExcuseSubmission::whereNotNull('attachments')->chunkById(100, function ($submissions) use (
            $dryRun,
            $publicDisk,
            $privateDisk,
            &$moved,
            &$missing,
            &$external
        ): void {
            foreach ($submissions as $submission) {
                $attachments = $submission->attachments;
                if (!is_array($attachments) || $attachments === []) {
                    continue;
                }

                $updated = $attachments;
                $publicPathsToDelete = [];
                $cloudinaryAssetsToDelete = [];
                $changed = false;

                foreach ($attachments as $index => $path) {
                    if (!is_string($path) || $path === '') {
                        continue;
                    }
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        if ($dryRun) {
                            $external++;
                            continue;
                        }

                        try {
                            $asset = $this->parseCloudinaryAsset($path);
                            $remote = Http::timeout(20)->withOptions(['allow_redirects' => false])->get($path);
                            $body = $remote->body();
                            if (!$remote->successful() || strlen($body) > 10 * 1024 * 1024) {
                                throw new \RuntimeException('Remote attachment could not be downloaded within the size limit.');
                            }

                            $privatePath = 'excuses/' . Str::uuid() . $asset['extension'];
                            if (!$privateDisk->put($privatePath, $body)) {
                                throw new \RuntimeException('Private storage rejected the attachment.');
                            }

                            $updated[$index] = $privatePath;
                            $cloudinaryAssetsToDelete[] = $asset;
                            $changed = true;
                            $moved++;
                        } catch (\Throwable $exception) {
                            $external++;
                            $this->warn("Could not secure provider-hosted attachment for submission {$submission->id}; it remains unchanged. {$exception->getMessage()}");
                        }
                        continue;
                    }

                    $normalizedPath = str_replace('\\', '/', ltrim($path, '/\\'));
                    if (preg_match('~(^|/)\.\.?(/|$)~', $normalizedPath)) {
                        $missing++;
                        continue;
                    }

                    $isLegacyPath = false;
                    foreach (['excuse_attachments/', 'excuse_documents/', 'excuses/'] as $prefix) {
                        if (str_starts_with(strtolower($normalizedPath), $prefix)) {
                            $isLegacyPath = true;
                            break;
                        }
                    }
                    if (!$isLegacyPath) {
                        continue;
                    }

                    if (!$publicDisk->exists($normalizedPath)) {
                        if (!$privateDisk->exists($normalizedPath)) {
                            $missing++;
                        }
                        continue;
                    }

                    $extension = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
                    $privatePath = 'excuses/' . Str::uuid() . ($extension !== '' ? '.' . $extension : '');
                    if ($dryRun) {
                        $updated[$index] = $privatePath;
                        $publicPathsToDelete[] = $normalizedPath;
                        $changed = true;
                        $moved++;
                        continue;
                    }

                    $stream = $publicDisk->readStream($normalizedPath);
                    if (!is_resource($stream)) {
                        $missing++;
                        continue;
                    }

                    try {
                        $stored = $privateDisk->writeStream($privatePath, $stream);
                    } finally {
                        fclose($stream);
                    }

                    if (!$stored || !$privateDisk->exists($privatePath)) {
                        $this->error("Could not copy attachment for excuse submission {$submission->id}.");
                        continue;
                    }

                    $updated[$index] = $privatePath;
                    $publicPathsToDelete[] = $normalizedPath;
                    $changed = true;
                    $moved++;
                }

                if (!$changed) {
                    continue;
                }

                if (!$dryRun) {
                    $submission->forceFill(['attachments' => array_values($updated)])->save();
                    foreach ($publicPathsToDelete as $publicPath) {
                        $publicDisk->delete($publicPath);
                    }

                    foreach ($cloudinaryAssetsToDelete as $asset) {
                        try {
                            $deleteResult = app(\Cloudinary\Cloudinary::class)->uploadApi()->destroy($asset['public_id'], [
                                'resource_type' => $asset['resource_type'],
                                'type' => $asset['delivery_type'],
                                'invalidate' => true,
                            ])->getArrayCopy();
                            if (!in_array($deleteResult['result'] ?? null, ['ok', 'not found'], true)) {
                                $external++;
                                $this->warn("Private copy saved for submission {$submission->id}, but Cloudinary did not confirm public asset deletion ({$asset['public_id']}).");
                            }
                        } catch (\Throwable $exception) {
                            $external++;
                            $this->warn("Private copy saved for submission {$submission->id}, but Cloudinary asset {$asset['public_id']} still needs deletion: {$exception->getMessage()}");
                        }
                    }
                }
            }
        });

        $this->info(($dryRun ? 'Dry run: ' : '') . "{$moved} local attachments would be moved or were moved; {$missing} missing or unsafe paths.");
        if ($external > 0) {
            $this->warn("{$external} provider-hosted attachment URL(s) were not migrated; review them and rerun after fixing provider access/configuration.");
        }
        if ($dryRun) {
            $this->comment('Review this report, then rerun with --apply to update database paths and remove local public copies.');
        }

        if (!$dryRun && ($external > 0 || $missing > 0)) {
            $this->error('Attachment migration finished with unresolved files; review the warnings before considering the cleanup complete.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return array{public_id: string, resource_type: string, delivery_type: string, extension: string} */
    private function parseCloudinaryAsset(string $url): array
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'res.cloudinary.com') {
            throw new \RuntimeException('Only Cloudinary-hosted legacy attachments can be migrated automatically.');
        }

        $segments = array_values(array_filter(explode('/', trim((string) ($parts['path'] ?? ''), '/')), fn ($part) => $part !== ''));
        $resourceIndex = null;
        foreach ($segments as $index => $segment) {
            if (in_array($segment, ['image', 'video', 'raw'], true)
                && in_array($segments[$index + 1] ?? null, ['upload', 'private', 'authenticated'], true)) {
                $resourceIndex = $index;
                break;
            }
        }

        if ($resourceIndex === null) {
            throw new \RuntimeException('Cloudinary URL format is unsupported.');
        }

        $resourceType = $segments[$resourceIndex];
        $deliveryType = $segments[$resourceIndex + 1];
        $versionIndex = null;
        for ($index = $resourceIndex + 2, $count = count($segments); $index < $count; $index++) {
            if (preg_match('/^v\d+$/', $segments[$index])) {
                $versionIndex = $index;
                break;
            }
        }

        if ($versionIndex === null) {
            throw new \RuntimeException('Cloudinary URL has no version segment; refusing ambiguous public ID extraction.');
        }

        $publicIdParts = array_slice($segments, $versionIndex + 1);
        $filename = array_pop($publicIdParts);
        if (!is_string($filename) || $filename === '') {
            throw new \RuntimeException('Cloudinary URL does not contain an asset ID.');
        }

        $extension = '';
        if ($resourceType === 'raw') {
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            $extension = $fileExtension !== '' ? '.' . $fileExtension : '';
        } else {
            $filename = pathinfo($filename, PATHINFO_FILENAME);
        }

        $publicIdParts[] = $filename;
        $publicId = implode('/', $publicIdParts);
        if ($publicId === '') {
            throw new \RuntimeException('Cloudinary URL has an empty public ID.');
        }

        return [
            'public_id' => $publicId,
            'resource_type' => $resourceType,
            'delivery_type' => $deliveryType,
            'extension' => $extension,
        ];
    }
}
