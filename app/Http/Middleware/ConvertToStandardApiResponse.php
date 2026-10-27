<?php

namespace App\Http\Middleware;

use App\Http\Responses\StandardApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the standard response format for /api/v3 routes, reusing the v1 controllers unchanged.
 */
class ConvertToStandardApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Always answer with JSON (including exceptions such as 404s and auth failures).
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $authenticated = in_array('auth:sanctum', $request->route()?->gatherMiddleware() ?? [], true);

        [$status, $body] = StandardApiResponse::transform(
            $response->getStatusCode(),
            $response->getData(true),
            $authenticated
        );

        if ($body === null) {
            return response()->noContent($status, $this->passThroughHeaders($response));
        }

        return new JsonResponse($body, $status, $this->passThroughHeaders($response), $response->getEncodingOptions());
    }

    protected function passThroughHeaders(JsonResponse $response): array
    {
        $headers = $response->headers->all();
        unset($headers['content-type'], $headers['content-length']);

        return $headers;
    }
}
