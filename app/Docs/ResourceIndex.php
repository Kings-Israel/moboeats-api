<?php

namespace App\Docs;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Static index of the app's API Resource classes, used to name OpenAPI component schemas.
 *
 * For each resource it knows the schema name (`UserResource` => `User`), the field names its toArray() can output
 * (after the v3 flattening of `attributes` / `relationships`) and the other resources it embeds.
 */
class ResourceIndex
{
    /** @var array<string, array{schema: string, keys: string[], embeds: string[]}>|null */
    protected static ?array $resources = null;

    /**
     * @return array<string, array{schema: string, keys: string[], embeds: string[]}> keyed by class short name
     */
    public static function all(): array
    {
        if (static::$resources !== null) {
            return static::$resources;
        }

        $sources = [];
        foreach (Finder::create()->files()->in(app_path('Http/Resources'))->name('*Resource.php') as $file) {
            $sources[$file->getBasename('.php')] = $file->getContents();
        }

        static::$resources = [];
        foreach ($sources as $class => $source) {
            preg_match_all("/'([A-Za-z_]\w*)'\s*=>/", static::toArrayBody($source), $keys);

            static::$resources[$class] = [
                'schema' => Str::beforeLast($class, 'Resource'),
                'keys' => array_values(array_diff(array_unique($keys[1]), ['attributes', 'relationships'])),
                'embeds' => array_values(array_diff(static::referencedIn($source, array_keys($sources)), [$class])),
            ];
        }

        return static::$resources;
    }

    /**
     * Resource classes referenced in a piece of code, either directly or via their `<Name>Collection` class.
     *
     * @param  string[]|null  $known
     * @return string[]
     */
    public static function referencedIn(string $code, ?array $known = null): array
    {
        $known ??= array_keys(static::all());

        preg_match_all('/\b([A-Z]\w*?)(Resource|Collection)\b/', $code, $matches, PREG_SET_ORDER);

        $found = [];
        foreach ($matches as [, $base]) {
            if (in_array("{$base}Resource", $known, true)) {
                $found[] = "{$base}Resource";
            }
        }

        return array_values(array_unique($found));
    }

    protected static function toArrayBody(string $source): string
    {
        $start = strpos($source, 'function toArray');

        return $start === false ? '' : substr($source, $start);
    }
}
