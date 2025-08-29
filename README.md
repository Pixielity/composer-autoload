# ComposerAutoload Module

A Laravel module for custom autoloading similar to Magento 2's approach with PSR-4 support. This module provides a flexible and extensible autoloader system that can handle PSR-4 namespaces, class maps, and file inclusions.

## Features

- **PSR-4 Namespace Support**: Full PSR-4 autoloading with multiple path support
- **Class Map Support**: Direct class-to-file mapping for legacy or non-PSR-4 classes
- **File Inclusion**: Automatic inclusion of helper files and bootstraps
- **Configuration-Based**: JSON and PHP configuration file support
- **Laravel Integration**: Seamless integration with Laravel's service container
- **Extensible Architecture**: Clean interfaces and dependency injection
- **Magento 2 Style**: Similar architecture and patterns to Magento 2's autoloader
- **🚀 Dynamic Module Discovery**: Automatically discovers modules with PSR-4 autoload configuration
- **📦 Stub-based Generation**: Uses customizable stub templates for autoload file generation
- **⚡ Smart Caching**: Only regenerates when module configuration changes
- **🔧 Console Commands**: Easy management via Artisan commands
- **📁 Bootstrap Integration**: Seamlessly integrates with Laravel's bootstrap process

## Installation

### Via Composer

```bash
composer require modules/composer-autoload
```

### Manual Installation

1. Copy the module to your Laravel project:
   ```bash
   cp -r src/modules/ComposerAutoload /path/to/your/laravel/app/modules/
   ```

2. Register the service provider in `config/app.php`:
   ```php
   'providers' => [
       // Other providers...
       Pixielity\ComposerAutoload\Providers\ComposerAutoloadServiceProvider::class,
   ],
   ```

3. Publish the configuration:
   ```bash
   php artisan vendor:publish --tag=composer-autoload-config
   ```

## Configuration

### Laravel Configuration

After publishing, configure the module in `config/modules/composer-autoload.php`:

```php
<?php

return [
    'auto_register' => env('COMPOSER_AUTOLOAD_AUTO_REGISTER', false),
    
    'autoload' => [
        'psr-4' => [
            'App\Custom\' => base_path('app/Custom'),
            'MyVendor\Package\' => [
                base_path('packages/vendor/package/src'),
                base_path('packages/vendor/package/lib'),
            ],
        ],
        
        'classmap' => [
            'LegacyClass' => base_path('legacy/LegacyClass.php'),
            'CustomHelper' => base_path('helpers/CustomHelper.php'),
        ],
        
        'files' => [
            base_path('helpers/functions.php'),
            base_path('bootstrap/custom.php'),
        ],
    ],
];
```

### JSON Configuration

You can also use JSON configuration files:

```json
{
    "autoload": {
        "psr-4": {
            "MyApp\\": "/path/to/app",
            "MyVendor\\Package\\": "/path/to/vendor/package/src"
        },
        "classmap": {
            "LegacyClass": "/path/to/legacy/LegacyClass.php"
        },
        "files": [
            "/path/to/helpers/functions.php"
        ]
    },
    "auto_register": true
}
```

## Usage

### Basic Usage

```php
use Pixielity\ComposerAutoload\Services\AutoloaderManager;
use Pixielity\ComposerAutoload\Services\ClassMap;
use Pixielity\ComposerAutoload\Services\NamespaceMap;

// Create autoloader
$autoloader = new AutoloaderManager();

// Add PSR-4 namespaces
$autoloader->addNamespace('MyApp\Controllers\', '/path/to/app/Controllers');
$autoloader->addNamespace('MyApp\Models\', '/path/to/app/Models');

// Add class mappings
$autoloader->addClass('LegacyHelper', '/path/to/legacy/Helper.php');

// Add files to include
$autoloader->addFile('/path/to/helpers/functions.php');

// Register the autoloader
$autoloader->register();
```

### Laravel Integration

```php
// Via service container
$autoloader = app(Pixielity\ComposerAutoload\Interfaces\AutoloaderInterface::class);
$autoloader->addNamespace('MyNamespace\', '/path/to/namespace');
$autoloader->register();

// Via dependency injection
use Pixielity\ComposerAutoload\Interfaces\AutoloaderInterface;

class MyController extends Controller
{
    public function __construct(AutoloaderInterface $autoloader)
    {
        $autoloader->addNamespace('Dynamic\', '/dynamic/path');
        $autoloader->register();
    }
}
```

### Configuration-Based Setup

```php
use Pixielity\ComposerAutoload\Config\AutoloadConfig;
use Pixielity\ComposerAutoload\Services\AutoloaderManager;

// Load from file
$config = new AutoloadConfig();
$config->loadFromFile('/path/to/config.json');

// Create and configure autoloader
$autoloader = new AutoloaderManager();
$autoloader->addNamespaces($config->getPsr4Namespaces());
$autoloader->addClasses($config->getClassmap());
$autoloader->addFiles($config->getFiles());
$autoloader->register();
```

## Architecture

### Interfaces

- **`AutoloaderInterface`**: Main autoloader interface
- **`ClassMapInterface`**: Interface for class-to-file mappings
- **`NamespaceMapInterface`**: Interface for PSR-4 namespace mappings
- **`ConfigInterface`**: Interface for configuration management

### Services

- **`AutoloaderManager`**: Main autoloader implementation
- **`ClassMap`**: Class-to-file mapping service
- **`NamespaceMap`**: PSR-4 namespace mapping service

### Configuration

- **`AutoloadConfig`**: Configuration management with file loading support

### Providers

- **`ComposerAutoloadServiceProvider`**: Laravel service provider for dependency injection

## API Reference

### AutoloaderManager

```php
// Registration
$autoloader->register(): bool
$autoloader->unregister(): bool
$autoloader->isRegistered(): bool

// Namespace management
$autoloader->addNamespace(string $namespace, string $path, bool $prepend = false): self
$autoloader->addNamespaces(array $namespaces): self
$autoloader->getNamespaces(): array

// Class mapping
$autoloader->addClass(string $className, string $filePath): self
$autoloader->addClasses(array $classMap): self

// File inclusion
$autoloader->addFile(string $filePath): self
$autoloader->addFiles(array $files): self

// Loading
$autoloader->loadClass(string $className): ?bool
```

### Configuration

```php
// Loading
$config->loadFromFile(string $configPath): self
$config->merge(array $config): self

// Access
$config->get(string $key, $default = null): mixed
$config->set(string $key, $value): self
$config->has(string $key): bool
$config->all(): array

// Autoload specific
$config->getPsr4Namespaces(): array
$config->getClassmap(): array
$config->getFiles(): array
```

## Examples

Check the `Examples/BasicUsageExample.php` file for comprehensive usage examples including:

- Basic autoloader setup
- Configuration-based setup
- File-based configuration
- Laravel integration examples

Run examples:

```php
use Pixielity\ComposerAutoload\Examples\BasicUsageExample;

$example = new BasicUsageExample();
$example->runAllExamples();
```

## Environment Variables

- `COMPOSER_AUTOLOAD_AUTO_REGISTER`: Enable automatic registration (default: false)
- `COMPOSER_AUTOLOAD_CACHE`: Enable caching (default: false)
- `COMPOSER_AUTOLOAD_DEBUG`: Enable debug mode (default: false)

## Comparison with Magento 2

This module follows Magento 2's autoloader patterns:

| Feature | Magento 2 | This Module |
|---------|-----------|-------------|
| PSR-4 Support | ✅ | ✅ |
| Class Maps | ✅ | ✅ |
| File Inclusion | ✅ | ✅ |
| Interface-based | ✅ | ✅ |
| Dependency Injection | ✅ | ✅ |
| Configuration Files | ✅ | ✅ |
| Laravel Integration | ❌ | ✅ |

## Requirements

- PHP 8.0 or higher
- Laravel 9.0, 10.0, or 11.0
- Composer (for package management)

## License

This module is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, please open an issue on the project repository or contact the development team.

# ComposerAutoload Module

A comprehensive Laravel module for automatic class loading and discovery, inspired by Magento 2's autoloader system. This module provides PSR-4 compliant autoloading with advanced class discovery, caching, and management capabilities.

## Features

- **PSR-4 Compliant Autoloading**: Fully compliant with PSR-4 autoloading standards
- **Automatic Class Discovery**: Scans directories and automatically discovers PHP classes
- **Caching System**: Caches discovered classes for improved performance
- **Multiple Directory Support**: Register multiple directories with different namespaces
- **Statistics and Monitoring**: Track loading performance and statistics
- **Helper Utilities**: Namespace and file system helper classes
- **Laravel Integration**: Full Laravel service container integration
- **Configurable**: Extensive configuration options via config files
- **Facade Support**: Easy-to-use facade for common operations

## Installation

The module is already installed and configured in your Laravel application. To enable it:

1. Ensure the module is enabled:
```bash
php artisan module:enable ComposerAutoload
```

2. Publish configuration (optional):
```bash
php artisan vendor:publish --provider="Pixielity\ComposerAutoload\Providers\ComposerAutoloadServiceProvider" --tag="config"
```

## Configuration

The main configuration file is located at `src/modules/ComposerAutoload/config/config.php`. Key configuration options include:

```php
'autoloader' => [
    'auto_register' => true,
    'prepend' => false,
    'file_extensions' => ['php'],
    'cache_file' => storage_path('framework/cache/composer_autoload_classes.json'),
    'auto_discover_directories' => [
        // 'directory_path' => 'namespace',
    ],
],
```

## Usage

### Basic Usage with Facade

```php
use Pixielity\ComposerAutoload\Facades\ComposerAutoloader;

// Initialize the autoloader with configuration
ComposerAutoloader::initialize([
    'auto_register' => true,
    'cache_file' => storage_path('custom_cache/classes.json'),
]);

// Register a directory for autoloading
ComposerAutoloader::registerDirectory(
    '/path/to/custom/classes',
    'Custom\\Classes'
);

// Register multiple directories at once
ComposerAutoloader::registerDirectories([
    '/path/to/modules' => 'App\\Modules',
    '/path/to/plugins' => 'App\\Plugins',
]);

// Get statistics
$stats = ComposerAutoloader::getStatistics();
echo "Classes discovered: " . $stats['classes_discovered'];
```

### Using Service Container

```php
// Get the autoloader manager
$autoloader = app(Pixielity\ComposerAutoload\Interfaces\AutoloaderManagerInterface::class);

// Or use the alias
$autoloader = app('composer.autoloader');

// Register custom directory
$autoloader->registerDirectory(
    base_path('custom/classes'),
    'Custom\\Classes',
    ['recursive' => true]
);

// Refresh class discovery
$autoloader->refresh();
```

### Direct Service Usage

```php
use Pixielity\ComposerAutoload\Interfaces\ClassLoaderInterface;
use Pixielity\ComposerAutoload\Interfaces\ClassRegistryInterface;
use Pixielity\ComposerAutoload\Interfaces\ClassDiscoveryInterface;

// Class Loader - PSR-4 autoloading
$classLoader = app(ClassLoaderInterface::class);
$classLoader->addPsr4('MyNamespace\\', '/path/to/classes');
$classLoader->register();

// Class Registry - Direct class-to-file mapping
$registry = app(ClassRegistryInterface::class);
$registry->registerClass('MyClass', '/path/to/MyClass.php');

// Class Discovery - Automatic class discovery
$discovery = app(ClassDiscoveryInterface::class);
$classes = $discovery->discoverClasses('/path/to/scan', 'BaseNamespace');
```

## Advanced Usage

### Custom Class Discovery

```php
use Pixielity\ComposerAutoload\Services\ClassDiscovery;

$discovery = new ClassDiscovery();

// Set custom file extensions
$discovery->setFileExtensions(['php', 'inc']);

// Discover classes with options
$classes = $discovery->discoverClasses('/path/to/classes', 'App\\Custom', [
    'recursive' => true,
    'extensions' => ['php'],
]);

// Get class information from file
$classInfo = $discovery->extractClassInfo('/path/to/SomeClass.php');
// Returns: ['namespace' => 'Some\\Namespace', 'class' => 'SomeClass']
```

### Using Helper Classes

```php
use Pixielity\ComposerAutoload\Helpers\NamespaceHelper;
use Pixielity\ComposerAutoload\Helpers\FileHelper;

// Namespace operations
$normalized = NamespaceHelper::normalize('App\\Custom\\Classes');
$className = NamespaceHelper::getClassName('App\\Custom\\Classes\\MyClass');
$namespace = NamespaceHelper::getNamespace('App\\Custom\\Classes\\MyClass');

// File operations
$isPhp = FileHelper::isPhpFile('/path/to/file.php');
$relativePath = FileHelper::getRelativePath('/base/path', '/base/path/sub/file.php');
$files = FileHelper::getFiles('/directory', '*.php', true);
```

### Configuration Examples

#### Environment Variables

Add these to your `.env` file:

```env
COMPOSER_AUTOLOAD_AUTO_REGISTER=true
COMPOSER_AUTOLOAD_PREPEND=false
COMPOSER_AUTOLOAD_CACHE_FILE=/custom/path/cache.json
COMPOSER_AUTOLOAD_STATISTICS=true
COMPOSER_AUTOLOAD_DEBUG=false
```

#### Runtime Configuration

```php
use Pixielity\ComposerAutoload\Facades\ComposerAutoloader;

ComposerAutoloader::initialize([
    'autoloader' => [
        'auto_register' => true,
        'prepend' => false,
        'file_extensions' => ['php'],
        'auto_discover_directories' => [
            base_path('custom') => 'Custom',
            app_path('Modules') => 'App\\Modules',
        ],
    ],
    'performance' => [
        'enable_statistics' => true,
        'cache_cleanup_interval' => 3600,
    ],
    'debug' => [
        'enable_logging' => false,
        'log_channel' => 'daily',
    ],
]);
```

## Common Use Cases

### 1. Custom Module System

```php
// Register module directories
ComposerAutoloader::registerDirectory(
    base_path('modules/blog'),
    'Pixielity\\Blog'
);

ComposerAutoloader::registerDirectory(
    base_path('modules/shop'),
    'Pixielity\\Shop'
);

// Now you can use classes like:
// new Pixielity\Blog\Services\PostService();
// new Pixielity\Shop\Models\Product();
```

### 2. Plugin System

```php
// Discover and register all plugins
$pluginDir = base_path('plugins');
$pluginDirs = glob($pluginDir . '/*', GLOB_ONLYDIR);

foreach ($pluginDirs as $dir) {
    $pluginName = basename($dir);
    ComposerAutoloader::registerDirectory($dir, "Plugins\\{$pluginName}");
}
```

### 3. Custom Library Integration

```php
// Register external libraries
ComposerAutoloader::registerDirectory(
    base_path('libraries/custom-lib/src'),
    'CustomLib'
);

// Use without composer require
$service = new CustomLib\Services\SomeService();
```

### 4. Development Tools

```php
// Register development tools only in local environment
if (app()->environment('local')) {
    ComposerAutoloader::registerDirectory(
        base_path('dev-tools'),
        'DevTools'
    );
}
```

## Performance Considerations

1. **Enable Caching**: Always use caching in production environments
2. **Disable Statistics**: Turn off statistics collection in production
3. **Optimize Discovery**: Limit recursive scanning depth for large directories
4. **Cache Cleanup**: Set appropriate cache cleanup intervals

```php
// Production configuration
ComposerAutoloader::initialize([
    'autoloader' => [
        'cache_file' => storage_path('framework/cache/autoload_classes.json'),
    ],
    'performance' => [
        'enable_statistics' => false,
        'cache_cleanup_interval' => 7200, // 2 hours
    ],
]);
```

## Monitoring and Debugging

### Get Statistics

```php
$stats = ComposerAutoloader::getStatistics();

echo "Initialized at: " . date('Y-m-d H:i:s', $stats['initialized_at']) . "\n";
echo "Directories registered: " . $stats['directories_registered'] . "\n";
echo "Classes discovered: " . $stats['classes_discovered'] . "\n";
echo "Refresh count: " . $stats['refresh_count'] . "\n";

// Class loader specific stats
if (isset($stats['class_loader'])) {
    echo "Classes loaded: " . $stats['class_loader']['loaded_classes'] . "\n";
    echo "Failed loads: " . $stats['class_loader']['failed_loads'] . "\n";
}
```

### Clear Cache

```php
// Clear all caches
ComposerAutoloader::clearCache();

// Refresh class discovery
ComposerAutoloader::refresh();
```

### Debug Mode

Enable debug logging in configuration:

```php
'debug' => [
    'enable_logging' => true,
    'log_channel' => 'daily',
],
```

## API Reference

### AutoloaderManagerInterface

- `initialize(array $config = []): bool` - Initialize the autoloader system
- `registerDirectory(string $directory, string $namespace, array $options = []): bool` - Register a directory
- `registerDirectories(array $directories, array $options = []): bool` - Register multiple directories
- `refresh(): bool` - Refresh class discovery
- `setEnabled(bool $enabled): bool` - Enable/disable autoloader
- `isEnabled(): bool` - Check if enabled
- `getStatistics(): array` - Get statistics
- `clearCache(): bool` - Clear caches

### ClassLoaderInterface

- `addPsr4(string $prefix, $paths): bool` - Add PSR-4 namespace
- `loadClass(string $className): bool` - Load a class
- `register(bool $prepend = false): bool` - Register autoloader
- `unregister(): bool` - Unregister autoloader
- `getPrefixes(): array` - Get all prefixes

### ClassRegistryInterface

- `registerClass(string $className, string $filePath): bool` - Register class
- `registerClasses(array $classMap): bool` - Register multiple classes
- `getClassPath(string $className): ?string` - Get class file path
- `hasClass(string $className): bool` - Check if class is registered
- `getAllClasses(): array` - Get all registered classes

### ClassDiscoveryInterface

- `discoverClasses(string $directory, string $namespace, array $options = []): array` - Discover classes
- `extractClassInfo(string $filePath): ?array` - Extract class information
- `isValidClassFile(string $filePath): bool` - Check if file contains valid class
- `setFileExtensions(array $extensions): void` - Set file extensions to scan

## Error Handling

The module uses graceful error handling and logging:

```php
try {
    $success = ComposerAutoloader::registerDirectory('/invalid/path', 'Test');
    if (!$success) {
        Log::warning('Failed to register directory for autoloading');
    }
} catch (Exception $e) {
    Log::error('Autoloader error: ' . $e->getMessage());
}
```

## Testing

To test the autoloader functionality:

```php
// Create test class file
file_put_contents('/tmp/TestClass.php', '<?php namespace TestNS; class TestClass {}');

// Register directory
ComposerAutoloader::registerDirectory('/tmp', 'TestNS');

// Test class loading
$class = new TestNS\TestClass();
echo "Autoloading works!";
```

## Troubleshooting

### Common Issues

1. **Classes not found**: Check namespace mapping and directory structure
2. **Performance issues**: Enable caching and disable statistics in production
3. **Memory issues**: Limit recursive scanning depth
4. **Permission errors**: Ensure cache directory is writable

### Debug Steps

1. Check if autoloader is registered: `ComposerAutoloader::isEnabled()`
2. Verify directory registration: `ComposerAutoloader::getRegisteredDirectories()`
3. Check statistics: `ComposerAutoloader::getStatistics()`
4. Clear cache and refresh: `ComposerAutoloader::clearCache()` then `ComposerAutoloader::refresh()`

## Contributing

This module follows Laravel and PSR-4 standards. When contributing:

1. Follow PSR-4 namespace conventions
2. Add proper type hints and return types
3. Include comprehensive documentation
4. Add unit tests for new features
5. Maintain backward compatibility

## License

This module is part of your Laravel application and follows the same license terms.

---

## Autoload Generation

The module now includes automatic generation of custom autoload files for bootstrap integration.

### Console Commands

```bash
# Generate autoload file (only if needed)
php artisan autoload:generate

# Force regeneration even if up to date
php artisan autoload:generate --force

# Generate and publish to bootstrap directory
php artisan autoload:generate --publish
```

### Publish to Bootstrap Directory

```bash
# Publish the generated autoload file
php artisan vendor:publish --tag=autoload
```

### Bootstrap Integration

The service provider automatically appends the autoload include to your entry point files:
- `artisan` - For CLI commands  
- `public/index.php` - For web requests

The line is automatically inserted after the `LARAVEL_START` definition:
```php
// Auto-generated: Custom module autoloader
require_once __DIR__.'/bootstrap/autoload.php';
```

**Note**: The service provider intelligently checks if the include is already present and only adds it once. This happens automatically when the service provider boots, so no manual intervention is required.

### How It Works

1. **Module Discovery**: The system scans `src/modules/` for directories containing `composer.json` files with PSR-4 autoload configuration.

2. **Stub Processing**: Uses the `stubs/autoload.stub` template to generate a custom autoload.php file with module mappings.

3. **Autoload Generation**: Creates a complete autoload.php file at `storage/framework/cache/autoload.php`.

4. **Bootstrap Integration**: The generated file is published to `bootstrap/autoload.php`.

### Customization

You can modify the `stubs/autoload.stub` file to customize the generated autoload.php file. The following placeholders are available:

- `{{MODULE_MAPPINGS}}` - PSR-4 namespace to path mappings
- `{{MODULE_INITIALIZERS}}` - Module initialization code

### Module Structure for Autoloading

```
src/modules/YourModule/
├── composer.json          # Required: PSR-4 autoload config
├── module.json            # Optional: additional files
└── src/                   # Your module source code
```

### Example Generated Autoload

The generated `bootstrap/autoload.php` file looks like:

```php
<?php

/**
 * Custom Module Autoloader
 */

if (!function_exists('customModuleAutoloader')) {
    function customModuleAutoloader(string $class): bool
    {
        $moduleMap = [
            'Pixielity\\Auth\\' => '/path/to/src/modules/Auth/src/',
            'Pixielity\\ComposerAutoload\\' => '/path/to/src/modules/ComposerAutoload/src/',
        ];

        foreach ($moduleMap as $namespace => $path) {
            if (strpos($class, $namespace) === 0) {
                $relativePath = str_replace($namespace, '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = $path . $relativePath . '.php';
                
                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }
        }
        return false;
    }
}

spl_autoload_register('customModuleAutoloader', true, true);
```
