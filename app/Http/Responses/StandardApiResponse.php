<?php

namespace App\Http\Responses;

use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts a legacy (v1) API response into the standard format served by /api/v3.
 *
 * The data itself is unchanged; only its shape and status code are standardised:
 * - Success: the resource itself is the body (no `{status, message, data}` envelope, no `{"data": ...}` wrapper).
 *   Paginated lists become `{"data": [...], "meta": {current_page, per_page, total, last_page}}`.
 *   Resource objects nesting fields under `attributes` / `relationships` are flattened.
 *   A success with no data becomes `{"message": "..."}`, or 204 No Content when there is no message either.
 * - Error: `{"message": "...", "errors": {field: [messages]}}` for validation errors (always 422), otherwise
 *   `{"message": "...", "details": ...}`. A 401 returned after authentication succeeded becomes 403, and 402 becomes 409.
 *
 * This class is used both at runtime (ConvertToStandardApiResponse middleware) and when generating the OpenAPI spec,
 * so the documented examples always match what the API returns.
 */
class StandardApiResponse
{
    public const SUCCESS_STATUS = 'Request was succesful';

    public const ERROR_STATUS = 'Error has occured...';

    /**
     * @param  bool  $authenticated  whether the request passed authentication (route uses auth:sanctum)
     * @return array{0: int, 1: mixed} [status code, body]; a null body means no content
     */
    public static function transform(int $status, mixed $body, bool $authenticated): array
    {
        if (static::isEnvelope($body)) {
            if ($status >= 400 || $body['status'] === static::ERROR_STATUS) {
                return static::error($status >= 400 ? $status : 400, $body['message'], $body['data'], $authenticated);
            }

            return static::success($status, $body['data'], $body['message']);
        }

        if ($status >= 400) {
            if (is_array($body) && Arr::isAssoc($body) && array_key_exists('message', $body)) {
                return static::error($status, $body['message'], Arr::except($body, ['message', 'exception', 'file', 'line', 'trace']), $authenticated, $body);
            }

            return static::error($status, null, is_array($body) && array_keys($body) === ['data'] ? $body['data'] : $body, $authenticated);
        }

        return static::success($status, $body, null);
    }

    protected static function success(int $status, mixed $data, mixed $message): array
    {
        if (static::isEmpty($data)) {
            if (is_string($message) && $message !== '') {
                return [$status, ['message' => $message]];
            }

            return [Response::HTTP_NO_CONTENT, null];
        }

        if (is_string($data) && static::isEmpty($message)) {
            return [$status, ['message' => $data]];
        }

        if (is_scalar($data)) {
            $body = is_string($message) && $message !== '' ? ['message' => $message] : [];

            return [$status, $body + ['result' => $data]];
        }

        return [$status, static::normalizeData($data)];
    }

    /**
     * @param  array|null  $original  an already-standard Laravel error body, returned as-is apart from status adjustments
     */
    protected static function error(int $status, mixed $message, mixed $data, bool $authenticated, ?array $original = null): array
    {
        $errors = match (true) {
            static::isValidationBag($data) => $data,
            is_array($data) && static::isValidationBag($data['errors'] ?? null) => $data['errors'],
            static::isValidationBag($message) => $message,
            default => null,
        };

        if ($errors !== null) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
        } elseif ($status === Response::HTTP_UNAUTHORIZED && $authenticated && $message !== 'Unauthenticated.') {
            // The request was authenticated, so this is a permission problem.
            $status = Response::HTTP_FORBIDDEN;
        } elseif ($status === Response::HTTP_PAYMENT_REQUIRED) {
            // Used for "already exists" conflicts (e.g. an active cart), not for payments.
            $status = Response::HTTP_CONFLICT;
        }

        if ($original !== null && $errors === null) {
            if ($status === Response::HTTP_NOT_FOUND && str_starts_with((string) $original['message'], 'No query results')) {
                $original['message'] = 'Resource not found.';
            }

            return [$status, Arr::only($original, ['message', 'errors'])];
        }

        $text = match (true) {
            is_string($message) && $message !== '' => $message,
            $errors !== null => (string) Arr::first(Arr::flatten($errors)),
            is_string($data) && $data !== '' => $data,
            default => Response::$statusTexts[$status] ?? 'Error',
        };

        $body = ['message' => $text];

        if ($errors !== null) {
            $body['errors'] = $errors;
        } elseif (! static::isEmpty($data) && $data !== $text && $data !== $message) {
            $body['details'] = static::normalizeData($data);
        }

        return [$status, $body];
    }

    /**
     * Unwrap resource wrappers, reshape pagination and flatten JSON:API-style resource objects.
     */
    public static function normalizeData(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (Arr::isAssoc($data)) {
            // Resource collection / paginator serialised by Laravel: {data, links, meta} or {current_page, data, ...}
            if (array_key_exists('data', $data) && is_array($data['data']) && ! Arr::isAssoc($data['data'])) {
                $meta = static::paginationMeta($data);
                if ($meta !== null) {
                    return ['data' => static::normalizeData($data['data']), 'meta' => $meta];
                }
            }

            // Single resource wrapper: {"data": {...}} or {"data": [...]}
            if (array_keys($data) === ['data'] && is_array($data['data'])) {
                return static::normalizeData($data['data']);
            }

            foreach (['attributes', 'relationships'] as $nested) {
                if (isset($data[$nested]) && is_array($data[$nested]) && Arr::isAssoc($data[$nested])) {
                    $data = static::flatten($data, $nested);
                }
            }
        }

        return array_map(fn ($value) => static::normalizeData($value), $data);
    }

    /**
     * Move the fields of a nested `attributes` / `relationships` object up into the resource object.
     * Fields whose name is already taken stay nested, so no data is lost.
     */
    protected static function flatten(array $data, string $nested): array
    {
        $remaining = [];
        $flattened = Arr::except($data, [$nested]);

        foreach ($data[$nested] as $key => $value) {
            if (array_key_exists($key, $flattened)) {
                $remaining[$key] = $value;
            } else {
                $flattened[$key] = $value;
            }
        }

        if ($remaining !== []) {
            $flattened[$nested] = $remaining;
        }

        return $flattened;
    }

    protected static function paginationMeta(array $data): ?array
    {
        $source = isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : $data;

        if (! array_key_exists('current_page', $source) || ! array_key_exists('per_page', $source)) {
            return null;
        }

        return array_filter([
            'current_page' => (int) $source['current_page'],
            'per_page' => (int) $source['per_page'],
            'total' => isset($source['total']) ? (int) $source['total'] : null,
            'last_page' => isset($source['last_page']) ? (int) $source['last_page'] : null,
        ], fn ($value) => $value !== null);
    }

    public static function isEnvelope(mixed $body): bool
    {
        return is_array($body)
            && count($body) === 3
            && array_key_exists('status', $body)
            && array_key_exists('message', $body)
            && array_key_exists('data', $body)
            && in_array($body['status'], [static::SUCCESS_STATUS, static::ERROR_STATUS], true);
    }

    /**
     * A validation message bag: {"field": ["message", ...], ...}.
     */
    protected static function isValidationBag(mixed $value): bool
    {
        if (! is_array($value) || $value === [] || ! Arr::isAssoc($value)) {
            return false;
        }

        foreach ($value as $messages) {
            if (! is_array($messages) || $messages === [] || Arr::isAssoc($messages) || array_filter($messages, fn ($m) => ! is_string($m))) {
                return false;
            }
        }

        return true;
    }

    protected static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
