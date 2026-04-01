<?php

declare(strict_types=1);

namespace PhilipRehberger\ResponseMacros\Tests;

use Orchestra\Testbench\TestCase;
use PhilipRehberger\ResponseMacros\ResponseMacroServiceProvider;

class RateLimitHeadersTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ResponseMacroServiceProvider::class,
        ];
    }

    public function test_with_rate_limit_sets_limit_header(): void
    {
        $response = response()->withRateLimit(100, 99);

        $this->assertSame('100', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_with_rate_limit_sets_remaining_header(): void
    {
        $response = response()->withRateLimit(100, 42);

        $this->assertSame('42', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_with_rate_limit_sets_retry_after_and_reset_when_provided(): void
    {
        $before   = time();
        $response = response()->withRateLimit(100, 0, 60);

        $this->assertSame('60', $response->headers->get('Retry-After'));

        $reset = (int) $response->headers->get('X-RateLimit-Reset');
        $this->assertGreaterThanOrEqual($before + 60, $reset);
        $this->assertLessThanOrEqual(time() + 60, $reset);
    }

    public function test_with_rate_limit_remaining_clamped_to_zero(): void
    {
        $response = response()->withRateLimit(100, -5);

        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_with_rate_limit_does_not_set_retry_after_when_null(): void
    {
        $response = response()->withRateLimit(100, 50);

        $this->assertNull($response->headers->get('Retry-After'));
        $this->assertNull($response->headers->get('X-RateLimit-Reset'));
    }
}
