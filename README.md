# Laravel Response Macros

[![Tests](https://github.com/philiprehberger/laravel-response-macros/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-response-macros/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/philiprehberger/laravel-response-macros/v/stable)](https://packagist.org/packages/philiprehberger/laravel-response-macros)
[![Total Downloads](https://poser.pugx.org/philiprehberger/laravel-response-macros/downloads)](https://packagist.org/packages/philiprehberger/laravel-response-macros)
[![License](https://poser.pugx.org/philiprehberger/laravel-response-macros/license)](https://packagist.org/packages/philiprehberger/laravel-response-macros)

A collection of response macros for consistent, standardized API responses in Laravel.

Stop hand-writing `['success' => true, 'data' => ...]` in every controller. Register once, use everywhere.

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0

## Installation

```bash
composer require philiprehberger/laravel-response-macros
```

The service provider is auto-discovered by Laravel. No manual registration is needed.

### Publish the config (optional)

```bash
php artisan vendor:publish --tag=response-macros-config
```

This copies `config/response-macros.php` to your application's config directory.

## Configuration

```php
// config/response-macros.php

return [
    // Key used to wrap data in the envelope() macro
    'envelope_key' => 'data',

    // Key used to nest metadata in the envelope() macro
    'meta_key' => 'meta',

    // When true, each response body includes a "status" key mirroring the HTTP status code
    'include_status_code' => true,
];
```

## Macros

### `response()->success()`

Returns a `200 OK` response (or any 2xx) indicating a successful operation.

**Signature**

```php
response()->success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
```

> **Note:** The `$status` parameter must be a 2xx status code (200–299). Passing a non-2xx status throws `InvalidArgumentException`.

**Example**

```php
return response()->success($user, 'User retrieved successfully');
```

**Response**

```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": { "id": 1, "name": "Jane Doe" },
    "status": 200
}
```

---

### `response()->error()`

Returns a `400 Bad Request` response (or any 4xx/5xx) indicating a failed operation.

**Signature**

```php
response()->error(string $message = 'Error', int $status = 400, mixed $errors = null): JsonResponse
```

> **Note:** The `$status` parameter must be a 4xx or 5xx status code (400–599). Passing a non-error status throws `InvalidArgumentException`.

**Example**

```php
return response()->error('Resource not found', 404);
```

**Response**

```json
{
    "success": false,
    "message": "Resource not found",
    "errors": null,
    "status": 404
}
```

With additional error detail:

```php
return response()->error('Payment failed', 402, ['code' => 'card_declined']);
```

```json
{
    "success": false,
    "message": "Payment failed",
    "errors": { "code": "card_declined" },
    "status": 402
}
```

---

### `response()->paginated()`

Wraps a `LengthAwarePaginator` with standardized pagination metadata.

**Signature**

```php
response()->paginated(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
```

**Example**

```php
$users = User::paginate(15);

return response()->paginated($users, 'Users retrieved');
```

**Response**

```json
{
    "success": true,
    "message": "Users retrieved",
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 4,
        "per_page": 15,
        "total": 60
    },
    "status": 200
}
```

---

### `response()->validationError()`

Returns a `422 Unprocessable Entity` response from a `Validator` instance or a `MessageBag`.

**Signature**

```php
response()->validationError(Validator|MessageBag $validator, string $message = 'The given data was invalid.'): JsonResponse
```

**Example with a Validator**

```php
$validator = Validator::make($request->all(), [
    'email' => 'required|email',
    'name'  => 'required|string|max:255',
]);

if ($validator->fails()) {
    return response()->validationError($validator);
}
```

**Example with a MessageBag**

```php
$messages = new \Illuminate\Support\MessageBag([
    'email' => ['This email address is already taken.'],
]);

return response()->validationError($messages);
```

**Response**

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "name":  ["The name field is required."]
    },
    "status": 422
}
```

You can customize the error message:

```php
return response()->validationError($validator, 'Please fix the highlighted fields.');
```

---

### `response()->noContent()`

> **Removed in v1.1.0.** The `noContent()` macro was dead code — Laravel's `ResponseFactory` defines `noContent()` natively, and native methods take precedence over macros. Use Laravel's built-in `response()->noContent()` instead, which returns an HTTP `204` with an empty body.

---

### `response()->accepted()`

Returns a `202 Accepted` response indicating the request has been queued or is being processed asynchronously.

**Signature**

```php
response()->accepted(mixed $data = null, string $message = 'Accepted'): JsonResponse
```

**Example**

```php
ProcessReportJob::dispatch($report);

return response()->accepted(['job_id' => $job->id], 'Report generation queued');
```

**Response**

```json
{
    "success": true,
    "message": "Report generation queued",
    "data": { "job_id": "abc-123" },
    "status": 202
}
```

---

### `response()->envelope()`

Wraps arbitrary data under a configurable key with optional metadata. Useful when you need full control over the response shape without the opinionated `success`/`message` fields.

**Signature**

```php
response()->envelope(mixed $data, array $meta = []): JsonResponse
```

**Example without metadata**

```php
return response()->envelope($product);
```

**Response**

```json
{
    "data": { "id": 42, "name": "Widget Pro" },
    "status": 200
}
```

**Example with metadata**

```php
return response()->envelope($results, [
    'version' => '2.1',
    'locale'  => 'en-US',
    'cached'  => true,
]);
```

**Response**

```json
{
    "data": [ ... ],
    "meta": {
        "version": "2.1",
        "locale": "en-US",
        "cached": true
    },
    "status": 200
}
```

---

## Omitting the Status Code from the Body

Set `include_status_code` to `false` in `config/response-macros.php` to remove the `"status"` key from all response bodies:

```php
'include_status_code' => false,
```

Before:

```json
{ "success": true, "message": "OK", "data": null, "status": 200 }
```

After:

```json
{ "success": true, "message": "OK", "data": null }
```

The HTTP status code on the response itself is never affected by this option.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

The MIT License (MIT). See the [LICENSE](LICENSE) file for details.
