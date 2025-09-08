<?php

declare(strict_types=1);

namespace PhilipRehberger\ResponseMacros\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\ResponseMacros\ResponseMacroServiceProvider;

class CursorPaginationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ResponseMacroServiceProvider::class,
        ];
    }

    public function test_cursor_paginated_returns_correct_structure(): void
    {
        $items     = [['id' => 1], ['id' => 2]];
        $paginator = new CursorPaginator($items, 2);

        $response = response()->cursorPaginated($paginator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertSame($items, $data['data']);
        $this->assertArrayHasKey('next_cursor', $data['meta']);
        $this->assertArrayHasKey('prev_cursor', $data['meta']);
        $this->assertArrayHasKey('has_more', $data['meta']);
        $this->assertArrayHasKey('per_page', $data['meta']);
    }

    public function test_cursor_paginated_custom_wrap_key(): void
    {
        $items     = [['id' => 1]];
        $paginator = new CursorPaginator($items, 1);

        $response = response()->cursorPaginated($paginator, 'results');
        $data     = $response->getData(true);

        $this->assertArrayHasKey('results', $data);
        $this->assertArrayNotHasKey('data', $data);
        $this->assertSame($items, $data['results']);
    }

    public function test_cursor_paginated_has_more_true_when_more_pages_exist(): void
    {
        // CursorPaginator detects "has more" when items count exceeds perPage
        $items     = [['id' => 1], ['id' => 2], ['id' => 3]];
        $paginator = new CursorPaginator($items, 2);

        $response = response()->cursorPaginated($paginator);
        $data     = $response->getData(true);

        $this->assertTrue($data['meta']['has_more']);
    }

    public function test_cursor_paginated_has_more_false_on_last_page(): void
    {
        $items     = [['id' => 1]];
        $paginator = new CursorPaginator($items, 2);

        $response = response()->cursorPaginated($paginator);
        $data     = $response->getData(true);

        $this->assertFalse($data['meta']['has_more']);
    }
}
