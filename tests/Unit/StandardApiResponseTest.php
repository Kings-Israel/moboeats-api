<?php

namespace Tests\Unit;

use App\Http\Responses\StandardApiResponse;
use PHPUnit\Framework\TestCase;

class StandardApiResponseTest extends TestCase
{
    private function envelope(mixed $data, mixed $message = null, bool $error = false): array
    {
        return [
            'status' => $error ? StandardApiResponse::ERROR_STATUS : StandardApiResponse::SUCCESS_STATUS,
            'message' => $message,
            'data' => $data,
        ];
    }

    public function test_success_envelope_returns_the_data_itself(): void
    {
        [$status, $body] = StandardApiResponse::transform(200, $this->envelope(['id' => 1, 'name' => 'Jane']), false);

        $this->assertSame(200, $status);
        $this->assertSame(['id' => 1, 'name' => 'Jane'], $body);
    }

    public function test_success_without_data_returns_message_or_no_content(): void
    {
        $this->assertSame([200, ['message' => 'Logged out']], StandardApiResponse::transform(200, $this->envelope('', 'Logged out'), true));
        $this->assertSame([204, null], StandardApiResponse::transform(200, $this->envelope(null), true));
    }

    public function test_scalar_data_is_wrapped(): void
    {
        $this->assertSame([200, ['result' => 5]], StandardApiResponse::transform(200, 5, true));
        $this->assertSame([200, ['message' => 'Done', 'result' => true]], StandardApiResponse::transform(200, $this->envelope(true, 'Done'), true));
    }

    public function test_resource_wrapper_is_removed_and_attributes_are_flattened(): void
    {
        $resource = ['data' => [
            'id' => 1,
            'uuid' => 'abc',
            'attributes' => ['name' => 'Jane', 'email' => 'jane@example.com'],
            'relationships' => ['user' => ['uuid' => 'u1', 'name' => 'Jane']],
        ]];

        [, $body] = StandardApiResponse::transform(201, $resource, true);

        $this->assertSame([
            'id' => 1,
            'uuid' => 'abc',
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'user' => ['uuid' => 'u1', 'name' => 'Jane'],
        ], $body);
    }

    public function test_relationships_without_attributes_are_flattened_without_overwriting_fields(): void
    {
        $user = ['id' => 1, 'name' => 'Jane', 'relationships' => ['orderer' => ['id' => 7], 'name' => 'Other']];

        [, $body] = StandardApiResponse::transform(200, ['data' => $user], true);

        $this->assertSame(['id' => 1, 'name' => 'Jane', 'orderer' => ['id' => 7], 'relationships' => ['name' => 'Other']], $body);
    }

    public function test_paginated_collection_becomes_data_and_meta(): void
    {
        $paginated = [
            'data' => [['id' => 1, 'attributes' => ['name' => 'A']]],
            'links' => ['first' => 'http://x?page=1', 'last' => 'http://x?page=3', 'prev' => null, 'next' => 'http://x?page=2'],
            'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 3, 'links' => [], 'path' => 'http://x', 'per_page' => 15, 'to' => 15, 'total' => 40],
        ];

        [, $body] = StandardApiResponse::transform(200, $paginated, true);

        $this->assertSame([
            'data' => [['id' => 1, 'name' => 'A']],
            'meta' => ['current_page' => 1, 'per_page' => 15, 'total' => 40, 'last_page' => 3],
        ], $body);
    }

    public function test_raw_paginator_inside_envelope_becomes_data_and_meta(): void
    {
        $paginator = ['current_page' => 2, 'data' => [['id' => 9]], 'per_page' => 10, 'total' => 11, 'last_page' => 2, 'next_page_url' => null];

        [, $body] = StandardApiResponse::transform(200, $this->envelope($paginator), true);

        $this->assertSame(['data' => [['id' => 9]], 'meta' => ['current_page' => 2, 'per_page' => 10, 'total' => 11, 'last_page' => 2]], $body);
    }

    public function test_validation_errors_become_422_with_errors(): void
    {
        $bag = ['email' => ['The email field is required.']];

        $this->assertSame(
            [422, ['message' => 'The email field is required.', 'errors' => $bag]],
            StandardApiResponse::transform(403, $this->envelope($bag, null, true), false)
        );
    }

    public function test_401_after_authentication_becomes_403(): void
    {
        $this->assertSame([403, ['message' => 'Unauthorized']], StandardApiResponse::transform(401, $this->envelope('', 'Unauthorized', true), true));
        $this->assertSame([401, ['message' => 'Unauthorized']], StandardApiResponse::transform(401, $this->envelope('', 'Unauthorized', true), false));
        $this->assertSame([401, ['message' => 'Unauthenticated.']], StandardApiResponse::transform(401, ['message' => 'Unauthenticated.'], true));
    }

    public function test_402_becomes_409_and_extra_data_is_kept_as_details(): void
    {
        $this->assertSame(
            [409, ['message' => 'You have active cart', 'details' => 'Cart']],
            StandardApiResponse::transform(402, $this->envelope('Cart', 'You have active cart', true), true)
        );
    }

    public function test_non_envelope_error_without_message(): void
    {
        $this->assertSame([422, ['message' => 'no_restaurant']], StandardApiResponse::transform(422, ['data' => 'no_restaurant'], true));
    }

    public function test_model_not_found_message_is_hidden(): void
    {
        $this->assertSame(
            [404, ['message' => 'Resource not found.']],
            StandardApiResponse::transform(404, ['message' => 'No query results for model [App\Models\Menu] 5'], true)
        );
    }

    public function test_error_envelope_with_success_status_code_becomes_400(): void
    {
        $this->assertSame([400, ['message' => 'Invalid code']], StandardApiResponse::transform(200, $this->envelope('', 'Invalid code', true), true));
    }
}
