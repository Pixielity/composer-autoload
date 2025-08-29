<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Interfaces;

/**
 * Interface AutoloaderInterface
 *
 * Main interface for autoloader functionality, similar to Magento 2's approach
 */
interface AutoloaderInterface
{
    /**
     * Register the autoloader with PHP's spl_autoload_register
     */
    public function register(): bool;

    /**
     * Unregister the autoloader
     */
    public function unregister(): bool;

    /**
     * Load a class by its name
     */
    public function loadClass(string $className): ?bool;

    /**
     * Add a namespace mapping
     */
    public function addNamespace(string $namespace, string $path, bool $prepend = false): self;

    /**
     * Add multiple namespace mappings
     */
    public function addNamespaces(array $namespaces): self;

    /**
     * Get all registered namespaces
     */
    public function getNamespaces(): array;

    /**
     * Check if autoloader is registered
     */
    public function isRegistered(): bool;

    /**
     * Add a class mapping
     */
    public function addClass(string $className, string $filePath): self;

    /**
     * Add multiple class mappings
     */
    public function addClasses(array $classMap): self;

    /**
     * Add a file to be included
     */
    public function addFile(string $filePath): self;

    /**
     * Add multiple files to be included
     */
    public function addFiles(array $files): self;

    /**
     * Get the class map instance
     */
    public function getClassMap(): \Modules\ComposerAutoload\Interfaces\ClassMapInterface;

    /**
     * Get the namespace map instance
     */
    public function getNamespaceMap(): \Modules\ComposerAutoload\Interfaces\NamespaceMapInterface;
}
