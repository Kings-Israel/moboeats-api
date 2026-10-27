<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class GenerateApiDocs extends Command
{
    protected $signature = 'docs:generate {--force : Discard any changes made to the extracted endpoint YAML files}';

    protected $description = 'Generate the API documentation and OpenAPI specs (YAML and JSON) for the v3 API and the legacy v1/v2 API';

    /**
     * Scribe config files to generate, one per documented API.
     */
    protected array $configs = ['scribe', 'scribe_legacy'];

    public function handle(): int
    {
        foreach ($this->configs as $config) {
            $this->info("Generating documentation from config/{$config}.php");

            $options = ['--config' => $config];
            if ($this->option('force')) {
                $options['--force'] = true;
            }

            $status = $this->call('scribe:generate', $options);

            if ($status !== self::SUCCESS) {
                return $status;
            }

            if (! $this->writeJsonSpec($config)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    protected function writeJsonSpec(string $config): bool
    {
        $yamlPath = config("{$config}.type") === 'static'
            ? base_path(config("{$config}.static.output_path").'/openapi.yaml')
            : storage_path("app/{$config}/openapi.yaml");

        if (! file_exists($yamlPath)) {
            $this->error("OpenAPI spec not found at {$yamlPath}");

            return false;
        }

        $jsonPath = preg_replace('/\.yaml$/', '.json', $yamlPath);

        file_put_contents(
            $jsonPath,
            json_encode(Yaml::parseFile($yamlPath, Yaml::PARSE_OBJECT_FOR_MAP), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $this->info("OpenAPI JSON spec written to {$jsonPath}");

        return true;
    }
}
