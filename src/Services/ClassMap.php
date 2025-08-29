<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Services;

use Modules\ComposerAutoload\Interfaces\ClassMapInterface;

/**
 * Class ClassMap
 *
 * Implementation of ClassMapInterface for managing class-to-file mappings
 */
class ClassMap implements ClassMapInterface
{
    /**
     * @var array Class to file mappings
     */
    protected array $classMap = [];

    /**
     * Constructor
     *
     * @param  array  $classMap  Initial class mappings
     */
    public function __construct(array $classMap = [])
    {
        $this->classMap = $classMap;
    }

    /**
     * Add a class to file mapping
     */
    public function addClass(string $className, string $filePath): self
    {
        $this->classMap[$className] = $this->normalizeFilePath($filePath);

        return $this;
    }

    /**
     * Add multiple class mappings
     */
    public function addClasses(array $classMap): self
    {
        foreach ($classMap as $className => $filePath) {
            $this->addClass($className, $filePath);
        }

        return $this;
    }

    /**
     * Get the file path for a class
     */
    public function getClassFile(string $className): ?string
    {
        return $this->classMap[$className] ?? null;
    }

    /**
     * Check if a class is mapped
     */
    public function hasClass(string $className): bool
    {
        return isset($this->classMap[$className]);
    }

    /**
     * Remove a class mapping
     */
    public function removeClass(string $className): self
    {
        unset($this->classMap[$className]);

        return $this;
    }

    /**
     * Get all class mappings
     */
    public function getAllClasses(): array
    {
        return $this->classMap;
    }

    /**
     * Clear all class mappings
     */
    public function clearClasses(): self
    {
        $this->classMap = [];

        return $this;
    }

    /**
     * Normalize file path
     */
    protected function normalizeFilePath(string $filePath): string
    {
        return rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath), DIRECTORY_SEPARATOR);
    }
}
