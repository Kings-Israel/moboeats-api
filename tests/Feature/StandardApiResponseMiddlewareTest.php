<?php

namespace Tests\Feature;

use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StandardApiResponseMiddlewareTest extends TestCase
{
    use HttpResponses;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'api.standard'])->prefix('api/v3-test')->group(function () {
            Route::get('/success', fn () => $this->success(['data' => ['id' => 1, 'attributes' => ['name' => 'Jane']]], 'OK'));
            Route::get('/error', fn () => $this->error(['name' => ['The name field is required.']], null, 403));
            Route::get('/empty', fn () => $this->success(null));
            Route::get('/missing', fn () => abort(404));
            Route::get('/secure', fn () => 'never')->middleware('auth:sanctum');
        });

        Route::middleware('api')->prefix('api/v1-test')->group(function () {
            Route::get('/success', fn () => $this->success(['id' => 1], 'OK'));
        });
    }

    public function test_success_is_unwrapped_and_flattened(): void
    {
        $this->getJson('/api/v3-test/success')->assertOk()->assertExactJson(['id' => 1, 'name' => 'Jane']);
    }

    public function test_validation_error_uses_422_and_errors(): void
    {
        $this->getJson('/api/v3-test/error')
            ->assertStatus(422)
            ->assertExactJson(['message' => 'The name field is required.', 'errors' => ['name' => ['The name field is required.']]]);
    }

    public function test_empty_success_is_no_content(): void
    {
        $this->getJson('/api/v3-test/empty')->assertNoContent();
    }

    public function test_exceptions_are_json_even_without_accept_header(): void
    {
        $this->get('/api/v3-test/missing')->assertNotFound()->assertJsonStructure(['message']);
        $this->get('/api/v3-test/secure')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_routes_without_the_middleware_keep_the_legacy_format(): void
    {
        $this->getJson('/api/v1-test/success')->assertOk()->assertExactJson([
            'status' => 'Request was succesful',
            'message' => 'OK',
            'data' => ['id' => 1],
        ]);
    }
}
