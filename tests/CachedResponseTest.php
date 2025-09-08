<?php

declare(strict_types=1);

namespace PhilipRehberger\ResponseMacros\Tests;

use Illuminate\Http\JsonResponse;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\ResponseMacros\ResponseMacroServiceProvider;

class CachedResponseTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ResponseMacroServiceProvider::class,
        ];
    }

    public function test_cached_sets_cache_control_header(): void
    {
        $response = response()->cached(['key' => 'value'], 1800);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertStringContainsString('max-age=1800', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    }

    public function test_cached_sets_etag_header_when_provided(): void
    {
        $response = response()->cached(['key' => 'value'], 3600, 'abc123');

        $this->assertSame('"abc123"', $response->headers->get('ETag'));
    }

    public function test_cached_returns_304_when_if_none_match_matches_etag(): void
    {
        $this->app['request']->headers->set('If-None-Match', '"abc123"');

        $response = response()->cached(['key' => 'value'], 3600, 'abc123');

        $this->assertSame(304, $response->getStatusCode());
    }

    public function test_cached_without_etag_does_not_set_etag_header(): void
    {
        $response = response()->cached(['key' => 'value']);

        $this->assertNull($response->headers->get('ETag'));
    }

    public function test_cached_default_ttl_is_3600(): void
    {
        $response = response()->cached(['key' => 'value']);

        $this->assertStringContainsString('max-age=3600', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    }
}
