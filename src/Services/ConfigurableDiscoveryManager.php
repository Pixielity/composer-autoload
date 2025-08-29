<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Services;

use Pixielity\ComposerAutoload\Interfaces\AutoloaderInterface;

/**
 * Class ConfigurableDiscoveryManager
 *
 * Manages auto-discovery based on configuration settings
 */
class ConfigurableDiscoveryManager
{
    protected ClassDiscovery $discovery;

    protected array $config = [];

    protected string $basePath;

    public function __construct(?ClassDiscovery $discovery = null, ?string $basePath = null)
    {
        $this->discovery = $discovery ?: new ClassDiscovery;
        $this->basePath = $basePath ?: dirname(__DIR__, 6); // Default to project root
    }

    /**
     * Load configuration from array
     */
    public function loadConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Load configuration from file
     */
    public function loadConfigFromFile(string $configPath): self
    {
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        }

        return $this;
    }

    /**
     * Perform auto-discovery based on configuration
     */
    public function performAutoDiscovery(AutoloaderInterface $autoloader): array
    {
        $discoveredNamespaces = [];

        // Check if discovery is enabled
        if (! $this->isDiscoveryEnabled()) {
            return $this->loadFallbackNamespaces($autoloader);
        }

        // Set up discovery options
        $this->configureDiscovery();

        // Process each configured directory
        $directories = $this->getDiscoveryDirectories();

        foreach ($directories as $name => $dirConfig) {
            $discovered = $this->processDiscoveryDirectory($autoloader, $name, $dirConfig);
            $discoveredNamespaces = array_merge($discoveredNamespaces, $discovered);
        }

        // Add fallback namespaces for any that weren't discovered
        $fallbacks = $this->loadFallbackNamespaces($autoloader, $discoveredNamespaces);
        $discoveredNamespaces = array_merge($discoveredNamespaces, $fallbacks);

        return $discoveredNamespaces;
    }

    /**
     * Check if auto-discovery is enabled
     */
    protected function isDiscoveryEnabled(): bool
    {
        return $this->config['discovery']['enabled'] ?? true;
    }

    /**
     * Configure the discovery service with global options
     */
    protected function configureDiscovery(): void
    {
        $options = $this->config['discovery']['options'] ?? [];

        if (isset($options['file_extensions'])) {
            $this->discovery->setFileExtensions($options['file_extensions']);
        }

        if (isset($options['exclude_directories'])) {
            $this->discovery->setExcludeDirectories($options['exclude_directories']);
        }
    }

    /**
     * Get discovery directories from config
     */
    protected function getDiscoveryDirectories(): array
    {
        return $this->config['discovery']['directories'] ?? [];
    }

    /**
     * Process a single discovery directory
     */
    protected function processDiscoveryDirectory(AutoloaderInterface $autoloader, string $name, array $dirConfig): array
    {
        $path = $this->resolvePath($dirConfig['path'] ?? '');

        if (! is_dir($path)) {
            return [];
        }

        $discovered = [];

        // Check if this is module discovery
        if ($dirConfig['module_discovery'] ?? false) {
            $discovered = $this->processModuleDiscovery($autoloader, $path, $dirConfig);
        } else {
            $discovered = $this->processRegularDiscovery($autoloader, $path, $dirConfig);
        }

        return $discovered;
    }

    /**
     * Process module-specific discovery
     */
    protected function processModuleDiscovery(AutoloaderInterface $autoloader, string $path, array $config): array
    {
        $baseNamespace = $config['base_namespace'] ?? 'Modules';
        $srcSubdir = $config['src_subdir'] ?? 'src';

        $options = [
            'src_subdir' => $srcSubdir,
        ];

        return $this->discovery->discoverModules($autoloader, $path, $baseNamespace, $options);
    }

    /**
     * Process regular directory discovery
     */
    protected function processRegularDiscovery(AutoloaderInterface $autoloader, string $path, array $config): array
    {
        $globalExcludeDirs = $this->config['discovery']['options']['exclude_directories'] ?? [];
        $localExcludeDirs = $config['exclude_directories'] ?? [];

        $options = [
            'recursive' => $config['recursive'] ?? true,
            'exclude_directories' => array_merge($globalExcludeDirs, $localExcludeDirs),
        ];

        $baseNamespace = $config['base_namespace'] ?? null;

        if ($baseNamespace) {
            // Use provided base namespace
            $normalizedNamespace = rtrim($baseNamespace, '\\').'\\';
            $autoloader->addNamespace($normalizedNamespace, $path);

            return [$normalizedNamespace => $path];
        } else {
            // Auto-discover namespaces
            $namespaces = $this->discovery->discoverNamespaces($path, $options);
            foreach ($namespaces as $namespace => $namespacePath) {
                $autoloader->addNamespace($namespace, $namespacePath);
            }

            return $namespaces;
        }
    }

    /**
     * Load fallback namespaces when discovery fails or is disabled
     */
    protected function loadFallbackNamespaces(AutoloaderInterface $autoloader, array $alreadyDiscovered = []): array
    {
        $fallbacks = $this->config['discovery']['fallback_namespaces'] ?? [];
        $loaded = [];

        foreach ($fallbacks as $namespace => $path) {
            // Only add fallback if not already discovered
            if (! isset($alreadyDiscovered[$namespace])) {
                $fullPath = $this->resolvePath($path);
                if (is_dir($fullPath)) {
                    $autoloader->addNamespace($namespace, $fullPath);
                    $loaded[$namespace] = $fullPath;
                }
            }
        }

        return $loaded;
    }

    /**
     * Resolve a path relative to the base path
     */
    protected function resolvePath(string $path): string
    {
        if (path_is_absolute($path)) {
            return $path;
        }

        return $this->basePath.DIRECTORY_SEPARATOR.$path;
    }

    /**
     * Get discovery statistics
     */
    public function getDiscoveryStatistics(): array
    {
        return [
            'discovery_enabled' => $this->isDiscoveryEnabled(),
            'configured_directories' => count($this->getDiscoveryDirectories()),
            'discovery_options' => $this->config['discovery']['options'] ?? [],
            'fallback_namespaces' => count($this->config['discovery']['fallback_namespaces'] ?? []),
            'discovered_classes' => count($this->discovery->getDiscoveredClasses()),
        ];
    }

    /**
     * Clear discovery cache
     */
    public function clearCache(): self
    {
        $this->discovery->clearCache();

        return $this;
    }

    /**
     * Get the discovery service instance
     */
    public function getDiscoveryService(): ClassDiscovery
    {
        return $this->discovery;
    }
}

/**
 * Check if a path is absolute
 */
function path_is_absolute(string $path): bool
{
    return $path[0] === DIRECTORY_SEPARATOR || (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:\\\\/', $path));
}
