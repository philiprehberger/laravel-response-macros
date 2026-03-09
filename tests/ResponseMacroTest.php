<?php

namespace PhilipRehberger\ResponseMacros\Tests;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\ResponseMacros\ResponseMacroServiceProvider;

class ResponseMacroTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ResponseMacroServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('response-macros.include_status_code', true);
        $app['config']->set('response-macros.envelope_key', 'data');
        $app['config']->set('response-macros.meta_key', 'meta');
    }

    // -------------------------------------------------------------------------
    // success()
    // -------------------------------------------------------------------------

    public function test_success_returns_200_by_default(): void
    {
        $response = response()->success();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_success_payload_structure(): void
    {
        $response = response()->success(['id' => 1], 'Created', 201);
        $data     = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertSame('Created', $data['message']);
        $this->assertSame(['id' => 1], $data['data']);
        $this->assertSame(201, $data['status']);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_success_with_null_data(): void
    {
        $response = response()->success();
        $data     = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertSame('Success', $data['message']);
        $this->assertNull($data['data']);
    }

    public function test_success_with_scalar_data(): void
    {
        $response = response()->success('hello');
        $data     = $response->getData(true);

        $this->assertSame('hello', $data['data']);
    }

    public function test_success_includes_status_code_when_configured(): void
    {
        $response = response()->success(null, 'OK', 200);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('status', $data);
        $this->assertSame(200, $data['status']);
    }

    public function test_success_omits_status_code_when_disabled(): void
    {
        config(['response-macros.include_status_code' => false]);

        $response = response()->success();
        $data     = $response->getData(true);

        $this->assertArrayNotHasKey('status', $data);

        // reset
        config(['response-macros.include_status_code' => true]);
    }

    // -------------------------------------------------------------------------
    // error()
    // -------------------------------------------------------------------------

    public function test_error_returns_400_by_default(): void
    {
        $response = response()->error();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_error_payload_structure(): void
    {
        $response = response()->error('Something went wrong', 500, ['detail' => 'server failure']);
        $data     = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertSame('Something went wrong', $data['message']);
        $this->assertSame(['detail' => 'server failure'], $data['errors']);
        $this->assertSame(500, $data['status']);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_error_with_null_errors(): void
    {
        $response = response()->error('Bad request');
        $data     = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertNull($data['errors']);
    }

    public function test_error_includes_status_code_when_configured(): void
    {
        $response = response()->error('Oops', 400);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('status', $data);
        $this->assertSame(400, $data['status']);
    }

    public function test_error_omits_status_code_when_disabled(): void
    {
        config(['response-macros.include_status_code' => false]);

        $response = response()->error();
        $data     = $response->getData(true);

        $this->assertArrayNotHasKey('status', $data);

        config(['response-macros.include_status_code' => true]);
    }

    // -------------------------------------------------------------------------
    // paginated()
    // -------------------------------------------------------------------------

    public function test_paginated_returns_200(): void
    {
        $paginator = $this->makePaginator();
        $response  = response()->paginated($paginator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_paginated_payload_structure(): void
    {
        $paginator = $this->makePaginator(total: 50, perPage: 10, currentPage: 2);
        $response  = response()->paginated($paginator, 'Records fetched');
        $data      = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertSame('Records fetched', $data['message']);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('meta', $data);

        $meta = $data['meta'];
        $this->assertSame(2, $meta['current_page']);
        $this->assertSame(5, $meta['last_page']);
        $this->assertSame(10, $meta['per_page']);
        $this->assertSame(50, $meta['total']);
    }

    public function test_paginated_data_contains_paginator_items(): void
    {
        $items     = [['id' => 1], ['id' => 2]];
        $paginator = new ConcretePaginator($items, 2, 2, 1);
        $response  = response()->paginated($paginator);
        $data      = $response->getData(true);

        $this->assertSame($items, $data['data']);
    }

    public function test_paginated_includes_status_code_when_configured(): void
    {
        $paginator = $this->makePaginator();
        $response  = response()->paginated($paginator);
        $data      = $response->getData(true);

        $this->assertArrayHasKey('status', $data);
        $this->assertSame(200, $data['status']);
    }

    // -------------------------------------------------------------------------
    // validationError()
    // -------------------------------------------------------------------------

    public function test_validation_error_returns_422(): void
    {
        $validator = Validator::make([], ['name' => 'required']);
        $response  = response()->validationError($validator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_validation_error_payload_structure_with_validator(): void
    {
        $validator = Validator::make([], ['name' => 'required', 'email' => 'required|email']);
        $response  = response()->validationError($validator);
        $data      = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertSame('The given data was invalid.', $data['message']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('name', $data['errors']);
        $this->assertArrayHasKey('email', $data['errors']);
    }

    public function test_validation_error_payload_structure_with_message_bag(): void
    {
        $messageBag = new \Illuminate\Support\MessageBag([
            'field' => ['This field is required.'],
        ]);
        $response = response()->validationError($messageBag);
        $data     = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertSame('The given data was invalid.', $data['message']);
        $this->assertArrayHasKey('field', $data['errors']);
    }

    public function test_validation_error_includes_status_code(): void
    {
        $validator = Validator::make([], ['name' => 'required']);
        $response  = response()->validationError($validator);
        $data      = $response->getData(true);

        $this->assertArrayHasKey('status', $data);
        $this->assertSame(422, $data['status']);
    }

    // -------------------------------------------------------------------------
    // noContent()
    // -------------------------------------------------------------------------

    public function test_no_content_returns_204(): void
    {
        // Laravel's ResponseFactory defines noContent() natively; the macro
        // registration is superseded by the framework method, which returns a
        // plain Response (not JsonResponse) with an empty body — the correct
        // HTTP 204 behaviour.
        $response = response()->noContent();

        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_no_content_body_is_empty(): void
    {
        $response = response()->noContent();

        $this->assertSame('', $response->getContent());
    }

    // -------------------------------------------------------------------------
    // accepted()
    // -------------------------------------------------------------------------

    public function test_accepted_returns_202(): void
    {
        $response = response()->accepted();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(202, $response->getStatusCode());
    }

    public function test_accepted_payload_structure(): void
    {
        $response = response()->accepted(['job' => 'queued'], 'Processing');
        $data     = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertSame('Processing', $data['message']);
        $this->assertSame(['job' => 'queued'], $data['data']);
        $this->assertSame(202, $data['status']);
    }

    public function test_accepted_with_null_data(): void
    {
        $response = response()->accepted();
        $data     = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertSame('Accepted', $data['message']);
        $this->assertNull($data['data']);
    }

    public function test_accepted_includes_status_code_when_configured(): void
    {
        $response = response()->accepted();
        $data     = $response->getData(true);

        $this->assertArrayHasKey('status', $data);
        $this->assertSame(202, $data['status']);
    }

    public function test_accepted_omits_status_code_when_disabled(): void
    {
        config(['response-macros.include_status_code' => false]);

        $response = response()->accepted();
        $data     = $response->getData(true);

        $this->assertArrayNotHasKey('status', $data);

        config(['response-macros.include_status_code' => true]);
    }

    // -------------------------------------------------------------------------
    // envelope()
    // -------------------------------------------------------------------------

    public function test_envelope_returns_200(): void
    {
        $response = response()->envelope(['id' => 1]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_envelope_payload_structure_without_meta(): void
    {
        $response = response()->envelope(['id' => 1]);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('data', $data);
        $this->assertSame(['id' => 1], $data['data']);
        $this->assertArrayNotHasKey('meta', $data);
    }

    public function test_envelope_payload_structure_with_meta(): void
    {
        $response = response()->envelope(['id' => 1], ['version' => '1.0', 'locale' => 'en']);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(['version' => '1.0', 'locale' => 'en'], $data['meta']);
    }

    public function test_envelope_respects_custom_envelope_key(): void
    {
        config(['response-macros.envelope_key' => 'payload']);

        $response = response()->envelope(['id' => 1]);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('payload', $data);
        $this->assertArrayNotHasKey('data', $data);

        config(['response-macros.envelope_key' => 'data']);
    }

    public function test_envelope_respects_custom_meta_key(): void
    {
        config(['response-macros.meta_key' => 'metadata']);

        $response = response()->envelope(['id' => 1], ['page' => 1]);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayNotHasKey('meta', $data);

        config(['response-macros.meta_key' => 'meta']);
    }

    public function test_envelope_includes_status_code_when_configured(): void
    {
        $response = response()->envelope([]);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('status', $data);
        $this->assertSame(200, $data['status']);
    }

    public function test_envelope_omits_status_code_when_disabled(): void
    {
        config(['response-macros.include_status_code' => false]);

        $response = response()->envelope([]);
        $data     = $response->getData(true);

        $this->assertArrayNotHasKey('status', $data);

        config(['response-macros.include_status_code' => true]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePaginator(int $total = 10, int $perPage = 5, int $currentPage = 1): LengthAwarePaginator
    {
        $items = array_map(fn (int $i): array => ['id' => $i], range(1, min($perPage, $total)));

        return new ConcretePaginator($items, $total, $perPage, $currentPage);
    }
}
