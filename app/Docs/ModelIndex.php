<?php

namespace App\Docs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Static index of the app's Eloquent models, used to name OpenAPI component schemas for endpoints that return
 * models directly (without an API Resource).
 *
 * A model's schema is named after the model (`Role`), or `<Model>Record` (`OrderRecord`) when an API Resource
 * schema with the same name exists, since the raw record has a different shape from the resource.
 */
class ModelIndex
{
    /** @var array<string, array{schema: string, keys: string[], embeds: string[]}>|null */
    protected static ?array $models = null;

    /**
     * @return array<string, array{schema: string, keys: string[], embeds: string[]}> keyed by class short name
     */
    public static function all(): array
    {
        if (static::$models !== null) {
            return static::$models;
        }

        $resourceSchemas = array_column(ResourceIndex::all(), 'schema');
        $sources = [];
        foreach (Finder::create()->files()->in(app_path('Models'))->depth(0)->name('*.php') as $file) {
            $sources[$file->getBasename('.php')] = $file->getContents();
        }

        static::$models = [];
        foreach ($sources as $name => $source) {
            $class = "App\\Models\\{$name}";
            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            preg_match_all('/\b([A-Z]\w*)::class\b/', $source, $related);

            static::$models[$name] = [
                'schema' => in_array($name, $resourceSchemas, true) ? "{$name}Record" : $name,
                'keys' => static::keys(new $class),
                'embeds' => array_values(array_intersect(array_unique($related[1]), array_keys($sources))),
            ];
        }

        return static::$models;
    }

    /**
     * Models referenced in a piece of code (static calls, `::class`, type hints).
     *
     * @return string[]
     */
    public static function referencedIn(string $code): array
    {
        preg_match_all('/\b([A-Z]\w*)(?:::|\s+\$)/', $code, $matches);

        return array_values(array_intersect(array_unique($matches[1]), array_keys(static::all())));
    }

    /**
     * @return string[]
     */
    protected static function keys(Model $model): array
    {
        try {
            $columns = Schema::getColumnListing($model->getTable());
        } catch (Throwable) {
            $columns = [];
        }

        if ($columns === []) {
            $columns = array_merge([$model->getKeyName()], $model->getFillable(), $model->usesTimestamps() ? ['created_at', 'updated_at'] : []);
        }

        return array_values(array_unique(array_merge(array_diff($columns, $model->getHidden()), $model->getAppends())));
    }
}
