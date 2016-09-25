<?php

namespace PhilipRehberger\ResponseMacros;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class ResponseMacroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/response-macros.php',
            'response-macros',
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/response-macros.php' => config_path('response-macros.php'),
        ], 'response-macros-config');

        $this->registerSuccessMacro();
        $this->registerErrorMacro();
        $this->registerPaginatedMacro();
        $this->registerValidationErrorMacro();
        $this->registerAcceptedMacro();
        $this->registerEnvelopeMacro();
    }

    protected function registerSuccessMacro(): void
    {
        ResponseFactory::macro(
            'success',
            function (mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse {
                /** @var ResponseFactory $this */
                if ($status < 200 || $status > 299) {
                    throw new InvalidArgumentException(
                        "The success macro requires a 2xx status code, {$status} given.",
                    );
                }

                $payload = [
                    'success' => true,
                    'message' => $message,
                    'data'    => $data,
                ];

                if (config('response-macros.include_status_code', true)) {
                    $payload['status'] = $status;
                }

                return response()->json($payload, $status);
            },
        );
    }

    protected function registerErrorMacro(): void
    {
        ResponseFactory::macro(
            'error',
            function (string $message = 'Error', int $status = 400, mixed $errors = null): JsonResponse {
                /** @var ResponseFactory $this */
                if ($status < 400 || $status > 599) {
                    throw new InvalidArgumentException(
                        "The error macro requires a 4xx or 5xx status code, {$status} given.",
                    );
                }

                $payload = [
                    'success' => false,
                    'message' => $message,
                    'errors'  => $errors,
                ];

                if (config('response-macros.include_status_code', true)) {
                    $payload['status'] = $status;
                }

                return response()->json($payload, $status);
            },
        );
    }

    protected function registerPaginatedMacro(): void
    {
        ResponseFactory::macro(
            'paginated',
            function (LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse {
                /** @var ResponseFactory $this */
                $payload = [
                    'success' => true,
                    'message' => $message,
                    'data'    => $paginator->items(),
                    'meta'    => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'per_page'     => $paginator->perPage(),
                        'total'        => $paginator->total(),
                    ],
                ];

                if (config('response-macros.include_status_code', true)) {
                    $payload['status'] = 200;
                }

                return response()->json($payload, 200);
            },
        );
    }

    protected function registerValidationErrorMacro(): void
    {
        ResponseFactory::macro(
            'validationError',
            function (Validator|MessageBag $validator, string $message = 'The given data was invalid.'): JsonResponse {
                /** @var ResponseFactory $this */
                $errors = $validator instanceof Validator
                    ? $validator->errors()->toArray()
                    : $validator->toArray();

                $payload = [
                    'success' => false,
                    'message' => $message,
                    'errors'  => $errors,
                ];

                if (config('response-macros.include_status_code', true)) {
                    $payload['status'] = 422;
                }

                return response()->json($payload, 422);
            },
        );
    }

    protected function registerAcceptedMacro(): void
    {
        ResponseFactory::macro(
            'accepted',
            function (mixed $data = null, string $message = 'Accepted'): JsonResponse {
                /** @var ResponseFactory $this */
                $payload = [
                    'success' => true,
                    'message' => $message,
                    'data'    => $data,
                ];

                if (config('response-macros.include_status_code', true)) {
                    $payload['status'] = 202;
                }

                return response()->json($payload, 202);
            },
        );
    }

    protected function registerEnvelopeMacro(): void
    {
        ResponseFactory::macro(
            'envelope',
            function (mixed $data, array $meta = []): JsonResponse {
                /** @var ResponseFactory $this */
                $envelopeKey = config('response-macros.envelope_key', 'data');
                $metaKey     = config('response-macros.meta_key', 'meta');

                $payload = [
                    $envelopeKey => $data,
                ];

                if (!empty($meta)) {
                    $payload[$metaKey] = $meta;
                }

                if (config('response-macros.include_status_code', true)) {
                    $payload['status'] = 200;
                }

                return response()->json($payload, 200);
            },
        );
    }
}
