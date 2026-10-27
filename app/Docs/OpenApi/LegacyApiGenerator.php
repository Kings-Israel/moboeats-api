<?php

namespace App\Docs\OpenApi;

use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;

/**
 * Marks every operation of the legacy (v1/v2) API as deprecated in favour of /api/v3.
 */
class LegacyApiGenerator extends OpenApiGenerator
{
    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        $pathItem['deprecated'] = true;

        return $pathItem;
    }
}
