<?php

namespace App\Docs\Strategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

/**
 * Marks an endpoint as authenticated in the generated docs when its route
 * (or controller) uses the `auth:sanctum` middleware, so the OpenAPI spec
 * always reflects the real route protection.
 */
class AuthFromSanctumMiddleware extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $middleware = $endpointData->route->gatherMiddleware();

        return [
            'authenticated' => in_array('auth:sanctum', $middleware, true),
        ];
    }
}
