<?php

namespace App\Docs\OpenApi;

use App\Docs\ModelIndex;
use App\Docs\ResourceIndex;
use App\Http\Responses\StandardApiResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\OpenApiGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Describes the /api/v3 (standard format) endpoints:
 * - Response examples documented on the controllers are converted with StandardApiResponse, exactly as the
 *   `api.standard` middleware converts real responses.
 * - Objects produced by an API Resource (e.g. `User`) or returned as a raw Eloquent model (e.g. `Role`, `OrderRecord`)
 *   are described once in `components.schemas` and referenced with `$ref`; request bodies are moved to
 *   `components.schemas` as `<Operation>Request`.
 * - Authenticated operations declare `security: [{BearerAuth: []}]`; public ones declare none.
 */
class StandardApiGenerator extends OpenApiGenerator
{
    /** @var array<string, array<string, array>> candidate ("resource:UserResource" / "model:Order") => [hash => sample object] */
    protected array $samples = [];

    /** @var array<string, array> */
    protected array $requestSchemas = [];

    public function pathItem(array $pathItem, array $groupedEndpoints, OutputEndpointData $endpoint): array
    {
        $authenticated = (bool) $endpoint->metadata->authenticated;

        if ($authenticated) {
            $pathItem['security'] = [['BearerAuth' => []]];
        } else {
            unset($pathItem['security']);
        }

        foreach ($pathItem['requestBody']['content'] ?? [] as $contentType => $content) {
            if (isset($content['schema']['properties'])) {
                $pathItem['requestBody']['content'][$contentType]['schema'] = [
                    '$ref' => '#/components/schemas/'.$this->registerRequestSchema($pathItem['operationId'], $content['schema']),
                ];
            }
        }

        $pathItem['responses'] = $this->responses($pathItem['responses'] ?? [], $endpoint, $authenticated);

        return $pathItem;
    }

    public function root(array $root, array $groupedEndpoints): array
    {
        $scheme = $root['components']['securitySchemes']['default'] ?? ['type' => 'http', 'scheme' => 'bearer'];
        unset($root['components']['securitySchemes']['default'], $root['security']);
        $root['components']['securitySchemes']['BearerAuth'] = $scheme;

        $schemas = array_map(fn ($schema) => $this->finalize($schema), $this->buildResourceSchemas()) + $this->requestSchemas;
        ksort($schemas);
        $root['components']['schemas'] = $schemas;

        return $root;
    }

    protected function responses(array $scribeResponses, OutputEndpointData $endpoint, bool $authenticated): array
    {
        $custom = $endpoint->metadata->custom ?? [];
        $candidates = array_merge(
            array_map(fn ($name) => "resource:{$name}", $custom['resources'] ?? []),
            array_map(fn ($name) => "model:{$name}", $custom['models'] ?? []),
        );
        $byStatus = [];

        foreach ($endpoint->responses as $response) {
            $description = $this->describe((string) $response->description);
            $decoded = json_decode((string) $response->content, true);

            if ($response->isBinary() || ($decoded === null && trim((string) $response->content) !== 'null')) {
                // Files, HTML, empty bodies: keep what Scribe generated.
                $byStatus[$response->status][] = ['raw' => $scribeResponses[$response->status] ?? ['description' => $description]];

                continue;
            }

            [$status, $body] = StandardApiResponse::transform($response->status, $decoded, $authenticated);
            $byStatus[$status][] = ['description' => $description, 'body' => $body];
        }

        ksort($byStatus);
        $responses = [];

        foreach ($byStatus as $status => $items) {
            $raw = Arr::first($items, fn ($item) => isset($item['raw']));
            if ($raw) {
                $responses[$status] = $raw['raw'];

                continue;
            }

            $descriptions = array_values(array_unique(array_filter(array_column($items, 'description'))));
            $responses[$status] = [
                'description' => implode(' / ', $descriptions) ?: (Response::$statusTexts[$status] ?? 'Response'),
            ];

            if ($status === Response::HTTP_NO_CONTENT) {
                continue;
            }

            $schemas = [];
            foreach ($items as $item) {
                $schema = $this->infer($item['body'], $candidates);
                $schemas[md5(json_encode($schema))] = $schema;
            }

            $content = ['schema' => $this->finalize(count($schemas) === 1 ? Arr::first($schemas) : ['oneOf' => array_values($schemas)])];

            if (count($items) === 1) {
                $content['example'] = $items[0]['body'];
            } else {
                foreach ($items as $i => $item) {
                    $key = Str::slug($item['description'] ?: "example {$i}", '_') ?: "example_{$i}";
                    $content['examples'][isset($content['examples'][$key]) ? "{$key}_{$i}" : $key] = [
                        'summary' => $item['description'] ?: "Example {$i}",
                        'value' => $item['body'],
                    ];
                }
            }

            $responses[$status]['content'] = ['application/json' => $content];
        }

        return $responses ?: ['200' => ['description' => 'Success']];
    }

    protected function describe(string $description): string
    {
        if (preg_match('/^\d{3},\s*(.+)$/', $description, $matches)) {
            return trim($matches[1]);
        }

        return preg_match('/^\d{3}$/', trim($description)) ? '' : trim($description);
    }

    protected function registerRequestSchema(string $operationId, array $schema): string
    {
        foreach ($this->requestSchemas as $name => $existing) {
            if ($existing == $schema) {
                return $name;
            }
        }

        $base = Str::studly($operationId).'Request';
        $name = $base;
        for ($i = 2; isset($this->requestSchemas[$name]); $i++) {
            $name = $base.$i;
        }

        $this->requestSchemas[$name] = $schema;

        return $name;
    }

    /**
     * Infer a JSON schema for an example value. Objects matching one of the candidate resources are recorded as
     * samples of that resource's component schema and referenced instead of inlined.
     */
    protected function infer(mixed $value, array $candidates, bool $withExamples = false): array
    {
        if ($value === null) {
            return ['nullable' => true];
        }

        if (is_bool($value)) {
            return $this->scalar('boolean', $value, $withExamples);
        }

        if (is_int($value)) {
            return $this->scalar('integer', $value, $withExamples);
        }

        if (is_float($value)) {
            return $this->scalar('number', $value, $withExamples);
        }

        if (is_string($value)) {
            $schema = $this->scalar('string', $value, $withExamples);
            $format = match (true) {
                (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) => 'uuid',
                (bool) preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?$/', $value) => 'date-time',
                (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) => 'date',
                filter_var($value, FILTER_VALIDATE_EMAIL) !== false => 'email',
                (bool) preg_match('#^https?://#', $value) => 'uri',
                default => null,
            };

            return $format ? $schema + ['format' => $format] : $schema;
        }

        if (! is_array($value)) {
            return [];
        }

        if ($value === [] || ! Arr::isAssoc($value)) {
            $items = array_map(fn ($item) => $this->infer($item, $candidates, $withExamples), $value);

            return ['type' => 'array', 'items' => $items ? $this->merge($items) : []];
        }

        if ($candidate = $this->matchSchema($value, $candidates)) {
            $this->samples[$candidate][md5(json_encode($value))] = $value;

            return ['$ref' => '#/components/schemas/'.$this->definition($candidate)['schema']];
        }

        return $this->object([$value], $candidates, $withExamples);
    }

    protected function scalar(string $type, mixed $value, bool $withExample): array
    {
        return $withExample ? ['type' => $type, 'example' => $value] : ['type' => $type];
    }

    /**
     * Schema for objects sharing a shape: union of their properties; required = present and non-null in all.
     *
     * @param  array[]  $objects
     */
    protected function object(array $objects, array $candidates, bool $withExamples): array
    {
        $values = [];
        foreach ($objects as $object) {
            foreach ($object as $key => $value) {
                $values[$key][] = $value;
            }
        }

        $properties = [];
        $required = [];
        foreach ($values as $key => $list) {
            $properties[$key] = $this->merge(array_map(fn ($value) => $this->infer($value, $candidates, $withExamples), $list));

            if (count($list) === count($objects) && ! in_array(null, $list, true)) {
                $required[] = (string) $key;
            }
        }

        $schema = ['type' => 'object'];
        if ($withExamples && $required) {
            $schema['required'] = $required;
        }
        $schema['properties'] = $properties ?: (object) [];

        return $schema;
    }

    /**
     * Merge schemas inferred from several values of the same field.
     *
     * @param  array[]  $schemas
     */
    protected function merge(array $schemas): array
    {
        $nullable = false;
        $unique = [];
        foreach ($schemas as $schema) {
            if ($schema === ['nullable' => true]) {
                $nullable = true;

                continue;
            }
            if ($schema === []) {
                // Unknown type (e.g. items of an empty array example): carries no information.
                continue;
            }
            $unique[md5(json_encode($schema))] = $schema;
        }
        $unique = array_values($unique);

        if ($unique === []) {
            return $nullable ? ['nullable' => true] : [];
        }

        $byType = [];
        foreach ($unique as $schema) {
            $byType[$schema['type'] ?? ('$ref:'.($schema['$ref'] ?? ''))][] = $schema;
        }

        $merged = match (true) {
            count($byType) === 1 => $this->mergeSameType($unique),
            array_diff(array_keys($byType), ['integer', 'number']) === [] => ['type' => 'number'],
            default => ['oneOf' => array_map(fn ($group) => $this->mergeSameType($group), array_values($byType))],
        };

        if (! $nullable) {
            return $merged;
        }

        return isset($merged['$ref']) ? ['allOf' => [$merged], 'nullable' => true] : $merged + ['nullable' => true];
    }

    /**
     * @param  array[]  $schemas  non-empty list of schemas sharing the same type (or the same $ref)
     */
    protected function mergeSameType(array $schemas): array
    {
        return match ($schemas[0]['type'] ?? null) {
            'object' => $this->mergeObjects($schemas),
            'array' => ['type' => 'array', 'items' => $this->merge(array_map(fn ($schema) => $schema['items'], $schemas))],
            default => $this->mergeScalars($schemas),
        };
    }

    protected function mergeObjects(array $schemas): array
    {
        $properties = [];
        foreach ($schemas as $schema) {
            foreach ((array) $schema['properties'] as $key => $property) {
                $properties[$key][] = $property;
            }
        }

        $merged = ['type' => 'object'];
        $required = array_values(array_intersect(...array_map(fn ($schema) => $schema['required'] ?? [], $schemas)));
        if ($required) {
            $merged['required'] = $required;
        }
        $merged['properties'] = $properties ? array_map(fn ($list) => $this->merge($list), $properties) : (object) [];

        return $merged;
    }

    protected function mergeScalars(array $schemas): array
    {
        $merged = $schemas[0];
        $formats = array_unique(array_map(fn ($schema) => $schema['format'] ?? '', $schemas));
        if (count($formats) > 1) {
            unset($merged['format']);
        }

        return $merged;
    }

    /**
     * Find the candidate whose fields cover the object's keys. API Resources are preferred over models; for models
     * only scalar fields are compared, since loaded relations are not table columns.
     */
    protected function matchSchema(array $object, array $candidates): ?string
    {
        foreach (['resource', 'model'] as $kind) {
            $keys = array_map('strval', array_keys($kind === 'model' ? array_filter($object, fn ($value) => ! is_array($value)) : $object));
            if (count($keys) < 2) {
                continue;
            }

            $best = null;
            $bestScore = [0, 0];
            foreach ($candidates as $candidate) {
                $fields = str_starts_with($candidate, "{$kind}:") ? ($this->definition($candidate)['keys'] ?? []) : [];
                if ($fields === []) {
                    continue;
                }

                $common = count(array_intersect($keys, $fields));
                $score = [$common / count($keys), $common / count($fields)];

                if ($score[0] >= 0.9 && $common >= min(3, count($fields)) && $score > $bestScore) {
                    [$best, $bestScore] = [$candidate, $score];
                }
            }

            if ($best) {
                return $best;
            }
        }

        return null;
    }

    /**
     * @return array{schema: string, keys: string[], embeds: string[]}|null
     */
    protected function definition(string $candidate): ?array
    {
        [$kind, $name] = explode(':', $candidate, 2);

        return ($kind === 'resource' ? ResourceIndex::all() : ModelIndex::all())[$name] ?? null;
    }

    /**
     * Turn internal "any" schemas (empty arrays) into `{}` so they serialise as JSON objects.
     */
    protected function finalize(array $schema): array|object
    {
        if ($schema === []) {
            return (object) [];
        }

        foreach (['items', 'not', 'additionalProperties'] as $key) {
            if (isset($schema[$key]) && is_array($schema[$key])) {
                $schema[$key] = $this->finalize($schema[$key]);
            }
        }

        foreach (['oneOf', 'anyOf', 'allOf'] as $key) {
            if (isset($schema[$key])) {
                $schema[$key] = array_map(fn ($sub) => $this->finalize($sub), $schema[$key]);
            }
        }

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            $schema['properties'] = $schema['properties'] === []
                ? (object) []
                : array_map(fn ($sub) => is_array($sub) ? $this->finalize($sub) : $sub, $schema['properties']);
        }

        return $schema;
    }

    /**
     * Build a component schema for every resource/model seen in the examples (including ones nested in them).
     */
    protected function buildResourceSchemas(): array
    {
        $schemas = [];
        $built = [];

        do {
            $changed = false;
            foreach ($this->samples as $resource => $samples) {
                if (($built[$resource] ?? null) === count($samples)) {
                    continue;
                }

                [$kind] = explode(':', $resource, 2);
                $definition = $this->definition($resource);

                $schemas[$definition['schema']] = $this->object(
                    array_values($samples),
                    array_map(fn ($name) => "{$kind}:{$name}", $definition['embeds']),
                    true
                );
                $built[$resource] = count($samples);
                $changed = true;
            }
        } while ($changed);

        return $schemas;
    }
}
