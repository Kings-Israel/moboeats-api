<?php

namespace App\Docs\Strategies;

use App\Docs\ModelIndex;
use App\Docs\ResourceIndex;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

/**
 * Records which API Resource classes and Eloquent models an endpoint's controller method uses, so the OpenAPI
 * generator can describe matching response objects with named component schemas (`$ref`).
 */
class ResourceHints extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $method = $endpointData->method;
        $file = $method?->getFileName();

        if (! $file || ! is_readable($file)) {
            return null;
        }

        $code = implode('', array_slice(file($file), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

        return [
            'custom' => array_merge($endpointData->metadata->custom ?? [], [
                'resources' => ResourceIndex::referencedIn($code),
                'models' => ModelIndex::referencedIn($code),
            ]),
        ];
    }
}
