<?php

namespace Pixielity\ComposerAutoload\Services;

use Illuminate\Support\Facades\File;

class AutoloadGenerator
{
    protected string $stubPath;

    protected string $outputPath;

    public function __construct()
    {
        $this->stubPath = __DIR__.'/../../stubs/autoload.stub';
        $this->outputPath = storage_path('framework/cache/autoload.php');
    }

    /**
     * Generate the autoload.php file.
     */
    public function generate(): void
    {
        $modules = $this->discoverModules();
        $mappings = $this->generateModuleMappings($modules);
        $initializers = $this->generateModuleInitializers($modules);

        $content = $this->generateAutoloadContent($mappings, $initializers);

        $this->ensureDirectoryExists(dirname($this->outputPath));
        File::put($this->outputPath, $content);
    }

    /**
     * Discover available modules.
     */
    protected function discoverModules(): array
    {
        $modules = [];
        $modulesPath = base_path('src/modules');

        if (! File::exists($modulesPath)) {
            return $modules;
        }

        $directories = File::directories($modulesPath);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $composerPath = $directory.'/composer.json';

            if (File::exists($composerPath)) {
                $composer = json_decode(File::get($composerPath), true);

                if (isset($composer['autoload']['psr-4'])) {
                    $modules[$moduleName] = [
                        'path' => $directory,
                        'psr4' => $composer['autoload']['psr-4'],
                        'composer' => $composer,
                    ];
                }
            }
        }

        return $modules;
    }

    /**
     * Generate module mappings for autoloader.
     */
    protected function generateModuleMappings(array $modules): string
    {
        $mappings = [];

        foreach ($modules as $moduleName => $moduleData) {
            foreach ($moduleData['psr4'] as $namespace => $path) {
                $fullPath = $moduleData['path'].'/'.trim($path, '/').'/';
                $mappings[] = "            '{$namespace}' => '{$fullPath}',";
            }
        }

        return implode("\n", $mappings);
    }

    /**
     * Generate module initializers.
     */
    protected function generateModuleInitializers(array $modules): string
    {
        $initializers = [];

        foreach ($modules as $moduleName => $moduleData) {
            // Check for module.json and any initialization requirements
            $moduleJsonPath = $moduleData['path'].'/module.json';

            if (File::exists($moduleJsonPath)) {
                $moduleJson = json_decode(File::get($moduleJsonPath), true);

                // Add any files that need to be loaded
                if (isset($moduleJson['files']) && is_array($moduleJson['files'])) {
                    foreach ($moduleJson['files'] as $file) {
                        $filePath = $moduleData['path'].'/'.ltrim($file, '/');
                        $initializers[] = "// Load file for module: {$moduleName}";
                        $initializers[] = "if (file_exists('{$filePath}')) {";
                        $initializers[] = "    require_once '{$filePath}';";
                        $initializers[] = '}';
                        $initializers[] = '';
                    }
                }
            }
        }

        return implode("\n", $initializers);
    }

    /**
     * Generate the complete autoload content.
     */
    protected function generateAutoloadContent(string $mappings, string $initializers): string
    {
        if (! File::exists($this->stubPath)) {
            throw new \RuntimeException("Autoload stub not found at: {$this->stubPath}");
        }

        $content = File::get($this->stubPath);

        $content = str_replace('{{MODULE_MAPPINGS}}', $mappings, $content);
        $content = str_replace('{{MODULE_INITIALIZERS}}', $initializers, $content);

        return $content;
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Get the output path.
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * Check if autoload file exists and is up to date.
     */
    public function isUpToDate(): bool
    {
        if (! File::exists($this->outputPath)) {
            return false;
        }

        $autoloadTime = File::lastModified($this->outputPath);
        $modulesPath = base_path('src/modules');

        if (! File::exists($modulesPath)) {
            return true;
        }

        // Check if any module has been modified since autoload generation
        $directories = File::directories($modulesPath);

        foreach ($directories as $directory) {
            $composerPath = $directory.'/composer.json';
            $moduleJsonPath = $directory.'/module.json';

            if (File::exists($composerPath) && File::lastModified($composerPath) > $autoloadTime) {
                return false;
            }

            if (File::exists($moduleJsonPath) && File::lastModified($moduleJsonPath) > $autoloadTime) {
                return false;
            }
        }

        return true;
    }
}
