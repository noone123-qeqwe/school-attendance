<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ExcuseAttachmentStorage
{
    public function diskName(): string
    {
        $diskName = (string) config('filesystems.private_uploads_disk', 'local');
        $diskConfig = config("filesystems.disks.{$diskName}");

        if ($diskName === '' || !is_array($diskConfig)
            || $diskName === 'public'
            || ($diskConfig['visibility'] ?? null) === 'public'
            || ($diskConfig['driver'] ?? null) === 'cloudinary') {
            throw new RuntimeException('Excuse attachments require a configured private storage disk.');
        }

        return $diskName;
    }

    public function store(UploadedFile $file): string
    {
        $path = $file->store('excuses', $this->diskName());
        if (!is_string($path) || $path === '') {
            throw new RuntimeException('The excuse attachment could not be stored.');
        }

        return $path;
    }
}
