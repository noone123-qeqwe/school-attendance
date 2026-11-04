<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CacheAndCdnHeadersTest extends TestCase
{
    public function test_storage_fallback_is_not_shared_cacheable(): void
    {
        $path = 'cache-check/' . bin2hex(random_bytes(8)) . '.txt';
        Storage::disk('public')->put($path, 'cache policy check');

        try {
            $this->get('/storage/' . $path)
                ->assertOk()
                ->assertHeader('Cache-Control', 'private, max-age=300, must-revalidate')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        } finally {
            Storage::disk('public')->delete($path);
        }
    }
}
