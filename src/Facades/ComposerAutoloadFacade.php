<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class ComposerAutoloadFacade
 *
 * Laravel facade for easy access to autoloader functionality
 *
 * @method static bool register()
 * @method static bool unregister()
 * @method static bool isRegistered()
 * @method static \Pixielity\ComposerAutoload\Services\AutoloaderManager addNamespace(string $namespace, string $path, bool $prepend = false)
 * @method static \Pixielity\ComposerAutoload\Services\AutoloaderManager addNamespaces(array $namespaces)
 * @method static array getNamespaces()
 * @method static \Pixielity\ComposerAutoload\Services\AutoloaderManager addClass(string $className, string $filePath)
 * @method static \Pixielity\ComposerAutoload\Services\AutoloaderManager addClasses(array $classMap)
 * @method static \Pixielity\ComposerAutoload\Services\AutoloaderManager addFile(string $filePath)
 * @method static \Pixielity\ComposerAutoload\Services\AutoloaderManager addFiles(array $files)
 * @method static bool|null loadClass(string $className)
 * @method static \Pixielity\ComposerAutoload\Interfaces\ClassMapInterface getClassMap()
 * @method static \Pixielity\ComposerAutoload\Interfaces\NamespaceMapInterface getNamespaceMap()
 */
class ComposerAutoloadFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'composer-autoload';
    }
}
