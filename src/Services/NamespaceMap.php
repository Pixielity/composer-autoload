<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Services;

use Modules\ComposerAutoload\Interfaces\NamespaceMapInterface;

/**
 * Class NamespaceMap
 *
 * Implementation of NamespaceMapInterface for PSR-4 namespace mapping
 */
class NamespaceMap implements NamespaceMapInterface
{
    /**
     * @var array Namespace to paths mappings
     */
    protected array $namespaces = [];

    /**
     * Constructor
     *
     * @param  array  $namespaces  Initial namespace mappings
     */
    public function __construct(array $namespaces = [])
    {
        $this->addNamespaces($namespaces);
    }

    /**
     * Add a namespace to path mapping
     */
    public function addNamespace(string $namespace, string $path, bool $prepend = false): self
    {
        $namespace = $this->normalizeNamespace($namespace);
        $path = $this->normalizePath($path);

        if (! isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = [];
        }

        if ($prepend) {
            array_unshift($this->namespaces[$namespace], $path);
        } else {
            $this->namespaces[$namespace][] = $path;
        }

        return $this;
    }

    /**
     * Add multiple namespace mappings
     */
    public function addNamespaces(array $namespaces): self
    {
        foreach ($namespaces as $namespace => $paths) {
            $paths = is_array($paths) ? $paths : [$paths];
            foreach ($paths as $path) {
                $this->addNamespace($namespace, $path);
            }
        }

        return $this;
    }

    /**
     * Get paths for a namespace
     */
    public function getNamespacePaths(string $namespace): array
    {
        $namespace = $this->normalizeNamespace($namespace);

        return $this->namespaces[$namespace] ?? [];
    }

    /**
     * Find file path for a class within namespaces
     */
    public function findFile(string $className): ?string
    {
        $className = ltrim($className, '\\');

        foreach ($this->namespaces as $namespace => $paths) {
            if (strpos($className, $namespace) === 0) {
                $relativeClass = substr($className, strlen($namespace));
                $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass).'.php';

                foreach ($paths as $path) {
                    $file = $path.DIRECTORY_SEPARATOR.$relativeClass;
                    if (file_exists($file)) {
                        return $file;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if a namespace is registered
     */
    public function hasNamespace(string $namespace): bool
    {
        $namespace = $this->normalizeNamespace($namespace);

        return isset($this->namespaces[$namespace]);
    }

    /**
     * Remove a namespace mapping
     */
    public function removeNamespace(string $namespace): self
    {
        $namespace = $this->normalizeNamespace($namespace);
        unset($this->namespaces[$namespace]);

        return $this;
    }

    /**
     * Get all namespace mappings
     */
    public function getAllNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Clear all namespace mappings
     */
    public function clearNamespaces(): self
    {
        $this->namespaces = [];

        return $this;
    }

    /**
     * Normalize namespace
     */
    protected function normalizeNamespace(string $namespace): string
    {
        return trim($namespace, '\\').'\\';
    }

    /**
     * Normalize path
     */
    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
