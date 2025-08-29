<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Providers;

use Illuminate\Support\ServiceProvider;
use Pixielity\ComposerAutoload\Commands\GenerateAutoloadCommand;
use Pixielity\ComposerAutoload\Config\AutoloadConfig;
use Pixielity\ComposerAutoload\Interfaces\AutoloaderInterface;
use Pixielity\ComposerAutoload\Interfaces\ClassMapInterface;
use Pixielity\ComposerAutoload\Interfaces\ConfigInterface;
use Pixielity\ComposerAutoload\Interfaces\NamespaceMapInterface;
use Pixielity\ComposerAutoload\Services\AutoloaderManager;
use Pixielity\ComposerAutoload\Services\AutoloadGenerator;
use Pixielity\ComposerAutoload\Services\ClassMap;
use Pixielity\ComposerAutoload\Services\NamespaceMap;
use Pixielity\ComposerAutoload\Installer\ComposerAutoloadInstaller;

/**
 * Class ComposerAutoloadServiceProvider
 *
 * Laravel service provider for the ComposerAutoload module
 */
class ComposerAutoloadServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [
        AutoloaderInterface::class => AutoloaderManager::class,
        ClassMapInterface::class => ClassMap::class,
        NamespaceMapInterface::class => NamespaceMap::class,
        ConfigInterface::class => AutoloadConfig::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AutoloaderInterface::class, function ($app) {
            $classMap = $app->make(ClassMapInterface::class);
            $namespaceMap = $app->make(NamespaceMapInterface::class);

            return new AutoloaderManager($classMap, $namespaceMap);
        });

        $this->app->singleton(ClassMapInterface::class, function ($app) {
            return new ClassMap;
        });

        $this->app->singleton(NamespaceMapInterface::class, function ($app) {
            return new NamespaceMap;
        });

        $this->app->singleton(ConfigInterface::class, function ($app) {
            return new AutoloadConfig;
        });

        // Register AutoloadGenerator
        $this->app->singleton(AutoloadGenerator::class, function ($app) {
            return new AutoloadGenerator;
        });

        // Register convenience alias
        $this->app->alias(AutoloaderInterface::class, 'composer-autoload');
        
        // Run auto-installer if not already installed
        $this->runAutoInstaller();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateAutoloadCommand::class,
            ]);
        }

        // Load default configuration if available
        $configPath = config_path('modules/composer-autoload.php');
        if (file_exists($configPath)) {
            $this->loadAutoloadConfiguration($configPath);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            AutoloaderInterface::class,
            ClassMapInterface::class,
            NamespaceMapInterface::class,
            ConfigInterface::class,
            'composer-autoload',
        ];
    }

    /**
     * Load autoload configuration and register with the autoloader
     */
    protected function loadAutoloadConfiguration(string $configPath): void
    {
        try {
            $config = $this->app->make(ConfigInterface::class);
            $config->loadFromFile($configPath);

            $autoloader = $this->app->make(AutoloaderInterface::class);

            // Add PSR-4 namespaces
            $psr4Namespaces = $config->getPsr4Namespaces();
            if (! empty($psr4Namespaces)) {
                $autoloader->addNamespaces($psr4Namespaces);
            }

            // Add classmap
            $classmap = $config->getClassmap();
            if (! empty($classmap)) {
                $autoloader->addClasses($classmap);
            }

            // Add files
            $files = $config->getFiles();
            if (! empty($files)) {
                $autoloader->addFiles($files);
            }

            // Register the autoloader if configured to do so
            if ($config->get('autoload.auto_register', false)) {
                $autoloader->register();
            }

        } catch (\Exception $e) {
            // Log error or handle gracefully
            if ($this->app->bound('log')) {
                $this->app['log']->error('Failed to load composer autoload configuration: '.$e->getMessage());
            }
        }
    }
    
    /**
     * Run the auto-installer if not already installed
     */
    protected function runAutoInstaller(): void
    {
        try {
            // Only run in console mode to avoid running during web requests
            if (!$this->app->runningInConsole()) {
                return;
            }
            
            // Check if already installed by looking for the bootstrap file
            $bootstrapFile = base_path('bootstrap/autoload.php');
            if (file_exists($bootstrapFile)) {
                $content = file_get_contents($bootstrapFile);
                // Check if it contains our signature
                if (strpos($content, 'ConfigurableDiscoveryManager') !== false) {
                    return; // Already installed
                }
            }
            
            // Run the installer
            ComposerAutoloadInstaller::installStandalone(base_path());
            
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want package installation to fail
            if ($this->app->bound('log')) {
                $this->app['log']->warning('ComposerAutoload auto-installer failed: ' . $e->getMessage());
            }
        }
    }
}
