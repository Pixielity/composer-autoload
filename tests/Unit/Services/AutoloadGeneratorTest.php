<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Tests\Unit\Services;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\File;
use Pixielity\ComposerAutoload\Services\AutoloadGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Mockery;

#[CoversClass(AutoloadGenerator::class)]
class AutoloadGeneratorTest extends TestCase
{
    private AutoloadGenerator $generator;

    private string $tempModulesDir;

    private string $tempStubPath;

    protected function setUp(): void
    {
        // Mock the Laravel application container
        $app = Mockery::mock(Container::class);
        $app->shouldReceive('storagePath')
            ->with('framework/cache/autoload.php')
            ->andReturn(COMPOSER_AUTOLOAD_TEST_TEMP . '/autoload.php');
        
        Container::setInstance($app);
        
        // Mock global functions if they don't exist
        if (!function_exists('storage_path')) {
            function storage_path($path = '') {
                return COMPOSER_AUTOLOAD_TEST_TEMP . '/' . ltrim($path, '/');
            }
        }
        
        if (!function_exists('base_path')) {
            function base_path($path = '') {
                return COMPOSER_AUTOLOAD_TEST_ROOT . '/' . ltrim($path, '/');
            }
        }

        $this->generator = new AutoloadGenerator;

        // Create temporary directories for testing
        $this->tempModulesDir = createTempDirectory('autoload_generator_modules');
        $this->tempStubPath = createTempFile('<?php
// AUTOLOAD STUB
{{MODULE_MAPPINGS}}

// MODULE INITIALIZERS
{{MODULE_INITIALIZERS}}
', '.stub');
    }

    protected function tearDown(): void
    {
        cleanupTempFiles();
        Mockery::close();
        Container::setInstance(null);
    }

    // === Construction Tests ===

    public function test_can_be_constructed(): void
    {
        $this->assertInstanceOf(AutoloadGenerator::class, $this->generator);
    }

    public function test_get_output_path(): void
    {
        $outputPath = $this->generator->getOutputPath();
        $this->assertIsString($outputPath);
        $this->assertStringContains('storage/framework/cache/autoload.php', $outputPath);
    }

    // === Module Discovery Tests ===

    public function test_discover_modules_with_no_modules_directory(): void
    {
        // Test when modules directory doesn't exist
        $reflection = new \ReflectionClass($this->generator);
        $discoverMethod = $reflection->getMethod('discoverModules');
        $discoverMethod->setAccessible(true);

        // Mock base_path to return non-existent directory
        $result = $discoverMethod->invoke($this->generator);

        $this->assertIsArray($result);
        // Should be empty when no modules directory exists
    }

    public function test_discover_modules_with_valid_modules(): void
    {
        // Create module structure
        $this->createTestModule('TestModule1', [
            'Pixielity\\TestModule1\\' => 'src/',
        ]);

        $this->createTestModule('TestModule2', [
            'Pixielity\\TestModule2\\' => 'src/',
            'Pixielity\\TestModule2\\Models\\' => 'src/Models/',
        ]);

        // Mock File facade for module discovery
        File::shouldReceive('exists')
            ->with($this->tempModulesDir)
            ->andReturn(true);

        File::shouldReceive('directories')
            ->with($this->tempModulesDir)
            ->andReturn([
                $this->tempModulesDir.'/TestModule1',
                $this->tempModulesDir.'/TestModule2',
            ]);

        File::shouldReceive('exists')
            ->with($this->tempModulesDir.'/TestModule1/composer.json')
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with($this->tempModulesDir.'/TestModule2/composer.json')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with($this->tempModulesDir.'/TestModule1/composer.json')
            ->andReturn(json_encode([
                'autoload' => [
                    'psr-4' => [
                        'Pixielity\\TestModule1\\' => 'src/',
                    ],
                ],
            ]));

        File::shouldReceive('get')
            ->with($this->tempModulesDir.'/TestModule2/composer.json')
            ->andReturn(json_encode([
                'autoload' => [
                    'psr-4' => [
                        'Pixielity\\TestModule2\\' => 'src/',
                        'Pixielity\\TestModule2\\Models\\' => 'src/Models/',
                    ],
                ],
            ]));

        $reflection = new \ReflectionClass($this->generator);
        $discoverMethod = $reflection->getMethod('discoverModules');
        $discoverMethod->setAccessible(true);

        $result = $discoverMethod->invoke($this->generator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('TestModule1', $result);
        $this->assertArrayHasKey('TestModule2', $result);

        // Test module structure
        $module1 = $result['TestModule1'];
        $this->assertArrayHasKey('path', $module1);
        $this->assertArrayHasKey('psr4', $module1);
        $this->assertArrayHasKey('composer', $module1);
    }

    public function test_discover_modules_ignores_invalid_modules(): void
    {
        // Create module without composer.json
        mkdir($this->tempModulesDir.'/InvalidModule', 0755, true);

        // Create module with invalid composer.json
        $invalidModuleDir = $this->tempModulesDir.'/InvalidJsonModule';
        mkdir($invalidModuleDir, 0755, true);
        file_put_contents($invalidModuleDir.'/composer.json', 'invalid json');

        File::shouldReceive('exists')
            ->with($this->tempModulesDir)
            ->andReturn(true);

        File::shouldReceive('directories')
            ->with($this->tempModulesDir)
            ->andReturn([
                $this->tempModulesDir.'/InvalidModule',
                $this->tempModulesDir.'/InvalidJsonModule',
            ]);

        File::shouldReceive('exists')
            ->with($this->tempModulesDir.'/InvalidModule/composer.json')
            ->andReturn(false);

        File::shouldReceive('exists')
            ->with($this->tempModulesDir.'/InvalidJsonModule/composer.json')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with($this->tempModulesDir.'/InvalidJsonModule/composer.json')
            ->andReturn('invalid json');

        $reflection = new \ReflectionClass($this->generator);
        $discoverMethod = $reflection->getMethod('discoverModules');
        $discoverMethod->setAccessible(true);

        $result = $discoverMethod->invoke($this->generator);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // === Mapping Generation Tests ===

    public function test_generate_module_mappings(): void
    {
        $modules = [
            'TestModule1' => [
                'path' => '/path/to/TestModule1',
                'psr4' => [
                    'Pixielity\\TestModule1\\' => 'src/',
                ],
            ],
            'TestModule2' => [
                'path' => '/path/to/TestModule2',
                'psr4' => [
                    'Pixielity\\TestModule2\\' => 'src/',
                    'Pixielity\\TestModule2\\Models\\' => 'src/Models/',
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->generator);
        $generateMappingsMethod = $reflection->getMethod('generateModuleMappings');
        $generateMappingsMethod->setAccessible(true);

        $result = $generateMappingsMethod->invoke($this->generator, $modules);

        $this->assertIsString($result);
        $this->assertStringContains("'Pixielity\\TestModule1\\'", $result);
        $this->assertStringContains("'Pixielity\\TestModule2\\'", $result);
        $this->assertStringContains("'Pixielity\\TestModule2\\Models\\'", $result);
        $this->assertStringContains('/path/to/TestModule1/src/', $result);
        $this->assertStringContains('/path/to/TestModule2/src/', $result);
        $this->assertStringContains('/path/to/TestModule2/src/Models/', $result);
    }

    public function test_generate_module_mappings_with_empty_modules(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $generateMappingsMethod = $reflection->getMethod('generateModuleMappings');
        $generateMappingsMethod->setAccessible(true);

        $result = $generateMappingsMethod->invoke($this->generator, []);

        $this->assertIsString($result);
        $this->assertEmpty($result);
    }

    // === Initializer Generation Tests ===

    public function test_generate_module_initializers_with_files(): void
    {
        $modules = [
            'TestModule1' => [
                'path' => '/path/to/TestModule1',
            ],
        ];

        File::shouldReceive('exists')
            ->with('/path/to/TestModule1/module.json')
            ->andReturn(true);

        File::shouldReceive('get')
            ->with('/path/to/TestModule1/module.json')
            ->andReturn(json_encode([
                'files' => ['bootstrap.php', 'helpers.php'],
            ]));

        $reflection = new \ReflectionClass($this->generator);
        $generateInitializersMethod = $reflection->getMethod('generateModuleInitializers');
        $generateInitializersMethod->setAccessible(true);

        $result = $generateInitializersMethod->invoke($this->generator, $modules);

        $this->assertIsString($result);
        $this->assertStringContains('// Load file for module: TestModule1', $result);
        $this->assertStringContains('/path/to/TestModule1/bootstrap.php', $result);
        $this->assertStringContains('/path/to/TestModule1/helpers.php', $result);
        $this->assertStringContains('require_once', $result);
        $this->assertStringContains('file_exists', $result);
    }

    public function test_generate_module_initializers_without_files(): void
    {
        $modules = [
            'TestModule1' => [
                'path' => '/path/to/TestModule1',
            ],
        ];

        File::shouldReceive('exists')
            ->with('/path/to/TestModule1/module.json')
            ->andReturn(false);

        $reflection = new \ReflectionClass($this->generator);
        $generateInitializersMethod = $reflection->getMethod('generateModuleInitializers');
        $generateInitializersMethod->setAccessible(true);

        $result = $generateInitializersMethod->invoke($this->generator, $modules);

        $this->assertIsString($result);
        $this->assertEmpty($result);
    }

    // === Content Generation Tests ===

    public function test_generate_autoload_content(): void
    {
        $mappings = "'Pixielity\\Test\\' => '/path/to/test'";
        $initializers = "require_once '/path/to/bootstrap.php';";

        File::shouldReceive('exists')
            ->with($this->tempStubPath)
            ->andReturn(true);

        File::shouldReceive('get')
            ->with($this->tempStubPath)
            ->andReturn('<?php
// AUTOLOAD STUB
{{MODULE_MAPPINGS}}

// MODULE INITIALIZERS
{{MODULE_INITIALIZERS}}');

        $reflection = new \ReflectionClass($this->generator);
        $generateContentMethod = $reflection->getMethod('generateAutoloadContent');
        $generateContentMethod->setAccessible(true);

        // Set stub path
        $stubPathProperty = $reflection->getProperty('stubPath');
        $stubPathProperty->setAccessible(true);
        $stubPathProperty->setValue($this->generator, $this->tempStubPath);

        $result = $generateContentMethod->invoke($this->generator, $mappings, $initializers);

        $this->assertIsString($result);
        $this->assertStringContains("'Pixielity\\Test\\'", $result);
        $this->assertStringContains('/path/to/test', $result);
        $this->assertStringContains('require_once \'/path/to/bootstrap.php\';', $result);
        $this->assertStringNotContains('{{MODULE_MAPPINGS}}', $result);
        $this->assertStringNotContains('{{MODULE_INITIALIZERS}}', $result);
    }

    public function test_generate_autoload_content_throws_exception_for_missing_stub(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Autoload stub not found at:');

        $reflection = new \ReflectionClass($this->generator);
        $generateContentMethod = $reflection->getMethod('generateAutoloadContent');
        $generateContentMethod->setAccessible(true);

        // Set non-existent stub path
        $stubPathProperty = $reflection->getProperty('stubPath');
        $stubPathProperty->setAccessible(true);
        $stubPathProperty->setValue($this->generator, '/nonexistent/stub.php');

        File::shouldReceive('exists')
            ->with('/nonexistent/stub.php')
            ->andReturn(false);

        $generateContentMethod->invoke($this->generator, '', '');
    }

    // === Directory Management Tests ===

    public function test_ensure_directory_exists(): void
    {
        $testDir = createTempDirectory('ensure_dir_test');
        $subDir = $testDir.'/sub/directory';

        File::shouldReceive('exists')
            ->with($testDir.'/sub')
            ->andReturn(false);

        File::shouldReceive('makeDirectory')
            ->with($testDir.'/sub', 0755, true)
            ->andReturn(true);

        $reflection = new \ReflectionClass($this->generator);
        $ensureDirMethod = $reflection->getMethod('ensureDirectoryExists');
        $ensureDirMethod->setAccessible(true);

        // Should not throw any exception
        $ensureDirMethod->invoke($this->generator, $testDir.'/sub');

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    // === Up-to-Date Check Tests ===

    public function test_is_up_to_date_with_non_existent_file(): void
    {
        File::shouldReceive('exists')
            ->with(storage_path('framework/cache/autoload.php'))
            ->andReturn(false);

        $result = $this->generator->isUpToDate();
        $this->assertFalse($result);
    }

    public function test_is_up_to_date_with_no_modules_directory(): void
    {
        File::shouldReceive('exists')
            ->with(storage_path('framework/cache/autoload.php'))
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with(base_path('src/modules'))
            ->andReturn(false);

        $result = $this->generator->isUpToDate();
        $this->assertTrue($result);
    }

    public function test_is_up_to_date_with_newer_modules(): void
    {
        $autoloadPath = storage_path('framework/cache/autoload.php');
        $modulesPath = base_path('src/modules');
        $moduleDir = $modulesPath.'/TestModule';

        File::shouldReceive('exists')
            ->with($autoloadPath)
            ->andReturn(true);

        File::shouldReceive('lastModified')
            ->with($autoloadPath)
            ->andReturn(1000); // Autoload file timestamp

        File::shouldReceive('exists')
            ->with($modulesPath)
            ->andReturn(true);

        File::shouldReceive('directories')
            ->with($modulesPath)
            ->andReturn([$moduleDir]);

        File::shouldReceive('exists')
            ->with($moduleDir.'/composer.json')
            ->andReturn(true);

        File::shouldReceive('lastModified')
            ->with($moduleDir.'/composer.json')
            ->andReturn(1500); // Newer than autoload file

        File::shouldReceive('exists')
            ->with($moduleDir.'/module.json')
            ->andReturn(false);

        $result = $this->generator->isUpToDate();
        $this->assertFalse($result);
    }

    // === Full Generation Integration Tests ===

    public function test_generate(): void
    {
        $outputPath = createTempFile('', '.php');

        // Mock dependencies
        File::shouldReceive('exists')
            ->with(base_path('src/modules'))
            ->andReturn(false); // No modules

        File::shouldReceive('exists')
            ->with($this->tempStubPath)
            ->andReturn(true);

        File::shouldReceive('get')
            ->with($this->tempStubPath)
            ->andReturn('<?php
// Generated autoload file
{{MODULE_MAPPINGS}}
{{MODULE_INITIALIZERS}}');

        File::shouldReceive('put')
            ->with($outputPath, \Mockery::type('string'))
            ->andReturn(true);

        // Set up generator with test paths
        $reflection = new \ReflectionClass($this->generator);

        $stubPathProperty = $reflection->getProperty('stubPath');
        $stubPathProperty->setAccessible(true);
        $stubPathProperty->setValue($this->generator, $this->tempStubPath);

        $outputPathProperty = $reflection->getProperty('outputPath');
        $outputPathProperty->setAccessible(true);
        $outputPathProperty->setValue($this->generator, $outputPath);

        // Should not throw any exception
        $this->generator->generate();

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    // === Helper Methods ===

    private function createTestModule(string $name, array $psr4Mappings): void
    {
        $moduleDir = $this->tempModulesDir.'/'.$name;
        mkdir($moduleDir, 0755, true);

        $composerJson = [
            'name' => 'test/'.strtolower($name),
            'autoload' => [
                'psr-4' => $psr4Mappings,
            ],
        ];

        file_put_contents($moduleDir.'/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT));

        // Create src directory
        mkdir($moduleDir.'/src', 0755, true);
    }

    #[DataProvider('pathNormalizationProvider')]
    public function test_path_normalization(string $input, string $expected): void
    {
        $modules = [
            'TestModule' => [
                'path' => '/base/path',
                'psr4' => [
                    'Test\\' => $input,
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->generator);
        $generateMappingsMethod = $reflection->getMethod('generateModuleMappings');
        $generateMappingsMethod->setAccessible(true);

        $result = $generateMappingsMethod->invoke($this->generator, $modules);

        $this->assertStringContains($expected, $result);
    }

    public static function pathNormalizationProvider(): array
    {
        return [
            'Simple path' => ['src/', '/base/path/src/'],
            'Path with trailing slash' => ['src//', '/base/path/src/'],
            'Path without trailing slash' => ['src', '/base/path/src/'],
            'Nested path' => ['src/Models/', '/base/path/src/Models/'],
        ];
    }
}
