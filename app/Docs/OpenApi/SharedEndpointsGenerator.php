<?php

namespace App\Docs\OpenApi;

use Illuminate\Support\Str;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;

/**
 * Keeps the OpenAPI spec valid when one controller method is routed from several URLs.
 *
 * - operationIds are derived from the endpoint summary, so they collide when the same docblock serves
 *   multiple routes. Colliding ones are replaced with an id built from the HTTP method and path.
 * - A method's @urlParam tags apply to all of its routes, so drop documented path parameters that
 *   don't appear in the path being written.
 */
class SharedEndpointsGenerator extends OpenApiGenerator
{
    private ?array $titleCounts = null;

    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        $this->titleCounts ??= collect($groupedEndpoints)
            ->flatMap(fn (array $group) => $group['endpoints'])
            ->countBy(fn (OutputEndpointData $e) => $e->metadata->title)
            ->all();

        $title = $endpoint->metadata->title;

        if (! $title || ($this->titleCounts[$title] ?? 0) > 1) {
            $parts = preg_split('/[^A-Za-z0-9]+/', $endpoint->uri, -1, PREG_SPLIT_NO_EMPTY);
            // Drop the leading `api/vN` segments.
            $parts = array_slice($parts, ($parts[0] ?? '') === 'api' && preg_match('/^v\d+$/', $parts[1] ?? '') ? 2 : 0);
            $pathItem['operationId'] = Str::lower($endpoint->httpMethods[0]).implode('', array_map('ucfirst', $parts));
        }

        return $pathItem;
    }

    public function pathParameters(array $parameters, array $endpoints, array $urlParameters): array
    {
        preg_match_all('#\{(\w+)\??}#', $endpoints[0]->uri, $matches);

        return array_filter($parameters, fn (array $parameter) => in_array($parameter['name'], $matches[1], true));
    }
}
