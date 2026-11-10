<?php

namespace App\Http\Controllers;

use App\Models\ExcuseSubmission;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcuseAttachmentController extends Controller
{
    public function show(Request $request, ExcuseSubmission $excuseSubmission, int $index): StreamedResponse|\Illuminate\Http\Response
    {
        $user = $request->user();
        if ($user->isAdmin() && !$request->session()->get('admin_2fa_verified', false)) {
            abort(403, 'Administrator verification is required to access excuse attachments.');
        }

        $attendance = $excuseSubmission->attendance()->with('subject')->first();

        $isAuthorized = $user->isAdmin()
            || (int) $excuseSubmission->user_id === (int) $user->id
            || ($user->isParent() && $user->children()->where('student_id', $excuseSubmission->user_id)->exists())
            || ($user->isTeacher() && $attendance && Subject::where('code', $attendance->subject_code)
                ->where('instructor_id', $user->id)->exists());

        abort_unless($isAuthorized, 403, 'You cannot access this excuse attachment.');

        $attachments = $excuseSubmission->attachments;
        abort_unless(is_array($attachments) && array_key_exists($index, $attachments), 404);

        return $this->streamAttachment($attachments[$index]);
    }

    private function streamAttachment(mixed $storedPath): StreamedResponse|\Illuminate\Http\Response
    {
        abort_unless(is_string($storedPath) && $storedPath !== '', 404);

        if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
            return $this->streamLegacyCloudinaryAttachment($storedPath);
        }

        $normalizedPath = str_replace('\\', '/', ltrim($storedPath, '/\\'));
        abort_if(str_contains($normalizedPath, "\0") || preg_match('~(^|/)\.\.?(/|$)~', $normalizedPath), 404);

        $privateDisk = Storage::disk(app(\App\Services\ExcuseAttachmentStorage::class)->diskName());
        if ($privateDisk->exists($normalizedPath)) {
            return $this->streamFromDisk($privateDisk, $normalizedPath);
        }

        // Serve pre-deployment local uploads only through this authorized route.
        $legacyPrefixes = ['excuse_attachments/', 'excuse_documents/', 'excuses/'];
        foreach ($legacyPrefixes as $prefix) {
            if (str_starts_with(strtolower($normalizedPath), $prefix) && Storage::disk('public')->exists($normalizedPath)) {
                return $this->streamFromDisk(Storage::disk('public'), $normalizedPath);
            }
        }

        abort(404);
    }

    private function streamLegacyCloudinaryAttachment(string $url): \Illuminate\Http\Response
    {
        $parts = parse_url($url);
        abort_unless(
            ($parts['scheme'] ?? null) === 'https'
                && ($parts['host'] ?? null) === 'res.cloudinary.com'
                && !isset($parts['user'])
                && !isset($parts['pass'])
                && !isset($parts['port']),
            404
        );

        $remote = Http::timeout(10)
            ->withOptions(['allow_redirects' => false])
            ->get($url);

        $body = $remote->body();
        abort_unless($remote->successful() && strlen($body) <= 10 * 1024 * 1024, 404);

        return response($body, 200, $this->safeHeaders(
            (string) $remote->header('Content-Type', 'application/octet-stream'),
            basename($parts['path'] ?? 'attachment')
        ));
    }

    private function streamFromDisk($disk, string $path): StreamedResponse
    {
        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        $mimeType = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
        $filename = basename($path);

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, $this->safeHeaders($mimeType, $filename));
    }

    /** @return array<string, string> */
    private function safeHeaders(string $mimeType, string $filename): array
    {
        if (!preg_match('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~i', $mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'attachment';
        $disposition = in_array(strtolower($mimeType), [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
        ], true) ? 'inline' : 'attachment';

        return [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ];
    }
}
