<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Services;

use Modules\ComposerAutoload\Interfaces\AutoloaderInterface;
use Modules\ComposerAutoload\Interfaces\ClassMapInterface;
use Modules\ComposerAutoload\Interfaces\NamespaceMapInterface;

/**
 * Class AutoloaderManager
 *
 * Main autoloader implementation similar to Magento 2's approach
 */
class AutoloaderManager implements AutoloaderInterface
{
    protected ClassMapInterface $classMap;

    protected NamespaceMapInterface $namespaceMap;

    protected bool $registered = false;

    /**
     * @var array Files to include
     */
    protected array $files = [];

    /**
     * Constructor
     */
    public function __construct(
        ?ClassMapInterface $classMap = null,
        ?NamespaceMapInterface $namespaceMap = null
    ) {
        $this->classMap = $classMap ?: new ClassMap;
        $this->namespaceMap = $namespaceMap ?: new NamespaceMap;
    }

    /**
     * Register the autoloader with PHP's spl_autoload_register
     */
    public function register(): bool
    {
        if ($this->registered) {
            return true;
        }

        $this->includeFiles();

        $registered = spl_autoload_register([$this, 'loadClass'], true, true);
        if ($registered) {
            $this->registered = true;
        }

        return $registered;
    }

    /**
     * Unregister the autoloader
     */
    public function unregister(): bool
    {
        if (! $this->registered) {
            return true;
        }

        $unregistered = spl_autoload_unregister([$this, 'loadClass']);
        if ($unregistered) {
            $this->registered = false;
        }

        return $unregistered;
    }

    /**
     * Load a class by its name
     */
    public function loadClass(string $className): ?bool
    {
        // Try class map first (fastest)
        $file = $this->classMap->getClassFile($className);
        if ($file !== null) {
            return $this->includeFile($file);
        }

        // Try PSR-4 namespace mapping
        $file = $this->namespaceMap->findFile($className);
        if ($file !== null) {
            return $this->includeFile($file);
        }

        // Class not found
        return null;
    }

    /**
     * Add a namespace mapping
     */
    public function addNamespace(string $namespace, string $path, bool $prepend = false): self
    {
        $this->namespaceMap->addNamespace($namespace, $path, $prepend);

        return $this;
    }

    /**
     * Add multiple namespace mappings
     */
    public function addNamespaces(array $namespaces): self
    {
        $this->namespaceMap->addNamespaces($namespaces);

        return $this;
    }

    /**
     * Get all registered namespaces
     */
    public function getNamespaces(): array
    {
        return $this->namespaceMap->getAllNamespaces();
    }

    /**
     * Check if autoloader is registered
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Add a class mapping
     */
    public function addClass(string $className, string $filePath): self
    {
        $this->classMap->addClass($className, $filePath);

        return $this;
    }

    /**
     * Add multiple class mappings
     */
    public function addClasses(array $classMap): self
    {
        $this->classMap->addClasses($classMap);

        return $this;
    }

    /**
     * Add a file to be included
     */
    public function addFile(string $filePath): self
    {
        $this->files[] = $this->normalizePath($filePath);

        return $this;
    }

    /**
     * Add multiple files to be included
     */
    public function addFiles(array $files): self
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }

        return $this;
    }

    /**
     * Get the class map instance
     */
    public function getClassMap(): ClassMapInterface
    {
        return $this->classMap;
    }

    /**
     * Get the namespace map instance
     */
    public function getNamespaceMap(): NamespaceMapInterface
    {
        return $this->namespaceMap;
    }

    /**
     * Include all registered files
     */
    protected function includeFiles(): void
    {
        foreach ($this->files as $file) {
            $this->includeFile($file);
        }
    }

    /**
     * Include a file safely
     */
    protected function includeFile(string $file): bool
    {
        if (file_exists($file)) {
            include_once $file;

            return true;
        }

        return false;
    }

    /**
     * Normalize file path
     */
    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
