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
            $response = $this->get('/storage/' . $path)
                ->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');

            $cacheControl = strtolower((string) $response->headers->get('Cache-Control'));
            $this->assertStringContainsString('private', $cacheControl);
            $this->assertStringContainsString('max-age=300', $cacheControl);
            $this->assertStringContainsString('must-revalidate', $cacheControl);
            $this->assertStringNotContainsString('public', $cacheControl);
        } finally {
            Storage::disk('public')->delete($path);
        }
    }
}
