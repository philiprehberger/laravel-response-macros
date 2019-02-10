# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.2] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.1.1] - 2026-03-16

### Changed
- Standardize composer.json: add homepage, scripts
- Add Development section to README

## [1.1.0] - 2026-03-12

### Added
- Optional `$message` parameter on `validationError()` macro for custom validation messages
- Status code range validation on `success()` (must be 2xx) and `error()` (must be 4xx/5xx)

### Removed
- `noContent()` macro — it was dead code shadowed by Laravel's native `ResponseFactory::noContent()` method; use Laravel's built-in `response()->noContent()` instead

### Fixed
- README incorrectly documented `noContent()` return type as `JsonResponse` (it was actually `Response`)

## [1.0.0] - 2026-03-09

### Added
- `response()->success()` macro for 2xx success responses.
- `response()->error()` macro for 4xx/5xx error responses.
- `response()->paginated()` macro with pagination metadata.
- `response()->validationError()` macro accepting a `Validator` instance or `MessageBag`.
- `response()->noContent()` macro for 204 responses.
- `response()->accepted()` macro for 202 responses.
- `response()->envelope()` macro for custom-keyed data wrapping with optional metadata.
- `config/response-macros.php` with `envelope_key`, `meta_key`, and `include_status_code` options.
- Laravel auto-discovery support via `composer.json` extra providers.
- Full PHPUnit test suite using Orchestra Testbench.
- GitHub Actions CI matrix for PHP 8.2, 8.3, and 8.4 against Laravel 11 and 12.

[Unreleased]: https://github.com/philiprehberger/laravel-response-macros/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/philiprehberger/laravel-response-macros/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/philiprehberger/laravel-response-macros/releases/tag/v1.0.0
