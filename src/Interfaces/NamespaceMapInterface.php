<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Interfaces;

/**
 * Interface NamespaceMapInterface
 *
 * Interface for managing namespace-to-path mappings (PSR-4 style)
 */
interface NamespaceMapInterface
{
    /**
     * Add a namespace to path mapping
     */
    public function addNamespace(string $namespace, string $path, bool $prepend = false): self;

    /**
     * Add multiple namespace mappings
     */
    public function addNamespaces(array $namespaces): self;

    /**
     * Get paths for a namespace
     */
    public function getNamespacePaths(string $namespace): array;

    /**
     * Find file path for a class within namespaces
     */
    public function findFile(string $className): ?string;

    /**
     * Check if a namespace is registered
     */
    public function hasNamespace(string $namespace): bool;

    /**
     * Remove a namespace mapping
     */
    public function removeNamespace(string $namespace): self;

    /**
     * Get all namespace mappings
     */
    public function getAllNamespaces(): array;

    /**
     * Clear all namespace mappings
     */
    public function clearNamespaces(): self;
}
