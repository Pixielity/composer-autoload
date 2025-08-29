<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class ClassDiscovery
 *
 * Service for automatically discovering PHP classes and their namespaces
 */
class ClassDiscovery
{
    /**
     * @var array File extensions to scan for PHP classes
     */
    protected array $fileExtensions = ['php'];

    /**
     * @var array Directories to exclude from scanning
     */
    protected array $excludeDirectories = [
        'vendor',
        'node_modules',
        '.git',
        'storage',
        'bootstrap/cache',
        'tests',
    ];

    /**
     * @var array Cache of discovered classes
     */
    protected array $discoveredClasses = [];

    /**
     * Discover all namespaces in a directory
     */
    public function discoverNamespaces(string $directory, array $options = []): array
    {
        $discovered = [];
        $classes = $this->discoverClasses($directory, $options);

        foreach ($classes as $classInfo) {
            $namespace = $classInfo['namespace'];
            $relativePath = $this->getNamespaceDirectory($classInfo['file'], $directory, $namespace, $classInfo['class']);

            if ($relativePath && $namespace) {
                if (! isset($discovered[$namespace])) {
                    $discovered[$namespace] = [];
                }
                if (! in_array($relativePath, $discovered[$namespace])) {
                    $discovered[$namespace][] = $relativePath;
                }
            }
        }

        // Convert arrays with single paths to strings for cleaner config
        foreach ($discovered as $namespace => $paths) {
            if (count($paths) === 1) {
                $discovered[$namespace] = $paths[0];
            }
        }

        return $discovered;
    }

    /**
     * Discover all classes in a directory
     */
    public function discoverClasses(string $directory, array $options = []): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $recursive = $options['recursive'] ?? true;
        $extensions = $options['extensions'] ?? $this->fileExtensions;
        $excludeDirs = array_merge($this->excludeDirectories, $options['exclude_directories'] ?? []);

        $classes = [];

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $iterator = new \DirectoryIterator($directory);
        }

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->isValidPhpFile($file->getPathname(), $extensions)) {
                $relativePath = str_replace($directory, '', $file->getPathname());

                // Skip excluded directories
                if ($this->shouldExcludeFile($relativePath, $excludeDirs)) {
                    continue;
                }

                $classInfo = $this->extractClassInfo($file->getPathname());
                if ($classInfo) {
                    $classes[] = array_merge($classInfo, ['file' => $file->getPathname()]);
                }
            }
        }

        $this->discoveredClasses = array_merge($this->discoveredClasses, $classes);

        return $classes;
    }

    /**
     * Extract class information from a PHP file
     */
    public function extractClassInfo(string $filePath): ?array
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if (! $content) {
            return null;
        }

        // Extract namespace
        $namespace = $this->extractNamespace($content);

        // Extract class/interface/trait names
        $classes = $this->extractClassNames($content);

        if (empty($classes)) {
            return null;
        }

        // Return info for the first class found (most common case)
        $className = $classes[0];

        return [
            'namespace' => $namespace,
            'class' => $className,
            'full_class' => $namespace ? $namespace.'\\'.$className : $className,
            'all_classes' => $classes,
        ];
    }

    /**
     * Auto-discover and register namespaces for an autoloader
     *
     * @param  \Pixielity\ComposerAutoload\Interfaces\AutoloaderInterface  $autoloader
     */
    public function autoDiscoverAndRegister($autoloader, array $directories, array $options = []): array
    {
        $discovered = [];

        foreach ($directories as $directory => $baseNamespace) {
            if (is_numeric($directory)) {
                // If no base namespace specified, discover all
                $directory = $baseNamespace;
                $namespaces = $this->discoverNamespaces($directory, $options);
            } else {
                // Use provided base namespace
                $classes = $this->discoverClasses($directory, $options);
                $namespaces = [$baseNamespace => $directory];

                // Also discover sub-namespaces if requested
                if ($options['discover_sub_namespaces'] ?? true) {
                    $subNamespaces = $this->discoverNamespaces($directory, $options);
                    $namespaces = array_merge($namespaces, $subNamespaces);
                }
            }

            foreach ($namespaces as $namespace => $path) {
                $autoloader->addNamespace($namespace, $path);
                $discovered[$namespace] = $path;
            }
        }

        return $discovered;
    }

    /**
     * Scan for module directories and auto-register them
     *
     * @param  \Pixielity\ComposerAutoload\Interfaces\AutoloaderInterface  $autoloader
     */
    public function discoverModules($autoloader, string $modulesDirectory, string $baseNamespace = 'Modules', array $options = []): array
    {
        if (! is_dir($modulesDirectory)) {
            return [];
        }

        $discovered = [];
        $srcSubdir = $options['src_subdir'] ?? 'src';

        $directories = glob($modulesDirectory.'/*', GLOB_ONLYDIR);

        foreach ($directories as $moduleDir) {
            $moduleName = basename($moduleDir);
            $srcDir = $moduleDir.DIRECTORY_SEPARATOR.$srcSubdir;

            if (is_dir($srcDir)) {
                $namespace = rtrim($baseNamespace, '\\').'\\'.$moduleName.'\\';
                $autoloader->addNamespace($namespace, $srcDir);
                $discovered[$namespace] = $srcDir;
            }
        }

        return $discovered;
    }

    /**
     * Extract namespace from PHP file content
     */
    protected function extractNamespace(string $content): ?string
    {
        if (preg_match('/^\s*namespace\s+([^;\s]+)/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract class names from PHP file content
     */
    protected function extractClassNames(string $content): array
    {
        $classes = [];

        // Match class, interface, trait, and enum declarations
        $patterns = [
            '/^\s*(?:abstract\s+|final\s+)?class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/m',
            '/^\s*interface\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/m',
            '/^\s*trait\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/m',
            '/^\s*enum\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/m',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $classes = array_merge($classes, $matches[1]);
            }
        }

        return array_unique($classes);
    }

    /**
     * Get the directory path for a namespace based on class file location
     */
    protected function getNamespaceDirectory(string $filePath, string $baseDirectory, ?string $namespace, string $className): ?string
    {
        if (! $namespace) {
            return null;
        }

        $relativePath = str_replace($baseDirectory, '', dirname($filePath));
        $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);

        // Convert namespace to path
        $namespacePath = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        // Find the base path by removing the namespace part from the relative path
        $basePath = str_replace($namespacePath, '', $relativePath);
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        return $baseDirectory.($basePath ? DIRECTORY_SEPARATOR.$basePath : '');
    }

    /**
     * Check if a file should be excluded from scanning
     */
    protected function shouldExcludeFile(string $relativePath, array $excludeDirs): bool
    {
        foreach ($excludeDirs as $excludeDir) {
            if (strpos($relativePath, $excludeDir) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a file is a valid PHP file
     */
    protected function isValidPhpFile(string $filePath, array $extensions): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return in_array($extension, $extensions);
    }

    /**
     * Set file extensions to scan
     */
    public function setFileExtensions(array $extensions): self
    {
        $this->fileExtensions = $extensions;

        return $this;
    }

    /**
     * Set directories to exclude from scanning
     */
    public function setExcludeDirectories(array $excludeDirectories): self
    {
        $this->excludeDirectories = $excludeDirectories;

        return $this;
    }

    /**
     * Get all discovered classes
     */
    public function getDiscoveredClasses(): array
    {
        return $this->discoveredClasses;
    }

    /**
     * Clear discovered classes cache
     */
    public function clearCache(): self
    {
        $this->discoveredClasses = [];

        return $this;
    }
}
