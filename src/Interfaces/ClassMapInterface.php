<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Interfaces;

/**
 * Interface ClassMapInterface
 *
 * Interface for managing class-to-file mappings
 */
interface ClassMapInterface
{
    /**
     * Add a class to file mapping
     */
    public function addClass(string $className, string $filePath): self;

    /**
     * Add multiple class mappings
     */
    public function addClasses(array $classMap): self;

    /**
     * Get the file path for a class
     */
    public function getClassFile(string $className): ?string;

    /**
     * Check if a class is mapped
     */
    public function hasClass(string $className): bool;

    /**
     * Remove a class mapping
     */
    public function removeClass(string $className): self;

    /**
     * Get all class mappings
     */
    public function getAllClasses(): array;

    /**
     * Clear all class mappings
     */
    public function clearClasses(): self;
}
