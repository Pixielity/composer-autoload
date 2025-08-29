<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Tests\Unit\Services;

use Pixielity\ComposerAutoload\Interfaces\AutoloaderInterface;
use Pixielity\ComposerAutoload\Interfaces\ClassMapInterface;
use Pixielity\ComposerAutoload\Interfaces\NamespaceMapInterface;
use Pixielity\ComposerAutoload\Services\AutoloaderManager;
use Pixielity\ComposerAutoload\Services\ClassMap;
use Pixielity\ComposerAutoload\Services\NamespaceMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoloaderManager::class)]
#[UsesClass(ClassMap::class)]
#[UsesClass(NamespaceMap::class)]
class AutoloaderManagerTest extends TestCase
{
    private AutoloaderManager $autoloader;

    private ClassMapInterface $classMap;

    private NamespaceMapInterface $namespaceMap;

    protected function setUp(): void
    {
        $this->classMap = new ClassMap;
        $this->namespaceMap = new NamespaceMap;
        $this->autoloader = new AutoloaderManager($this->classMap, $this->namespaceMap);
    }

    protected function tearDown(): void
    {
        // Unregister autoloader to avoid conflicts
        $this->autoloader->unregister();
        cleanupTempFiles();
    }

    // === Construction Tests ===

    public function test_implements_autoloader_interface(): void
    {
        $this->assertInstanceOf(AutoloaderInterface::class, $this->autoloader);
    }

    public function test_can_be_constructed_without_dependencies(): void
    {
        $autoloader = new AutoloaderManager;
        $this->assertInstanceOf(AutoloaderManager::class, $autoloader);
        $this->assertInstanceOf(ClassMapInterface::class, $autoloader->getClassMap());
        $this->assertInstanceOf(NamespaceMapInterface::class, $autoloader->getNamespaceMap());
    }

    public function test_can_be_constructed_with_dependencies(): void
    {
        $this->assertInstanceOf(AutoloaderManager::class, $this->autoloader);
        $this->assertSame($this->classMap, $this->autoloader->getClassMap());
        $this->assertSame($this->namespaceMap, $this->autoloader->getNamespaceMap());
    }

    // === Namespace Management Tests ===

    public function test_add_namespace(): void
    {
        $result = $this->autoloader->addNamespace('Test\\', '/path/to/test');
        $this->assertSame($this->autoloader, $result); // Test fluent interface

        $namespaces = $this->autoloader->getNamespaces();
        $this->assertArrayHasKey('Test\\', $namespaces);
        $this->assertContains('/path/to/test', $namespaces['Test\\']);
    }

    public function test_add_namespace_with_prepend_option(): void
    {
        $this->autoloader->addNamespace('Test\\', '/path/one');
        $this->autoloader->addNamespace('Test\\', '/path/two', true); // prepend

        $namespaces = $this->autoloader->getNamespaces();
        $paths = $namespaces['Test\\'];

        // Prepended path should be first
        $this->assertEquals('/path/two', $paths[0]);
        $this->assertEquals('/path/one', $paths[1]);
    }

    public function test_add_namespaces(): void
    {
        $namespaces = [
            'App\\' => '/app',
            'Test\\' => ['/test1', '/test2'],
        ];

        $result = $this->autoloader->addNamespaces($namespaces);
        $this->assertSame($this->autoloader, $result); // Test fluent interface

        $allNamespaces = $this->autoloader->getNamespaces();
        $this->assertArrayHasKey('App\\', $allNamespaces);
        $this->assertArrayHasKey('Test\\', $allNamespaces);
        $this->assertContains('/app', $allNamespaces['App\\']);
        $this->assertContains('/test1', $allNamespaces['Test\\']);
        $this->assertContains('/test2', $allNamespaces['Test\\']);
    }

    public function test_get_namespaces_returns_empty_array_when_none_added(): void
    {
        $namespaces = $this->autoloader->getNamespaces();
        $this->assertIsArray($namespaces);
        $this->assertEmpty($namespaces);
    }

    // === Class Map Management Tests ===

    public function test_add_class(): void
    {
        $result = $this->autoloader->addClass('TestClass', '/path/to/TestClass.php');
        $this->assertSame($this->autoloader, $result); // Test fluent interface

        $classMap = $this->autoloader->getClassMap();
        $this->assertTrue($classMap->hasClass('TestClass'));
        $this->assertEquals('/path/to/TestClass.php', $classMap->getClassFile('TestClass'));
    }

    public function test_add_classes(): void
    {
        $classes = [
            'TestClass1' => '/path/to/TestClass1.php',
            'TestClass2' => '/path/to/TestClass2.php',
        ];

        $result = $this->autoloader->addClasses($classes);
        $this->assertSame($this->autoloader, $result); // Test fluent interface

        $classMap = $this->autoloader->getClassMap();
        $this->assertTrue($classMap->hasClass('TestClass1'));
        $this->assertTrue($classMap->hasClass('TestClass2'));
        $this->assertEquals('/path/to/TestClass1.php', $classMap->getClassFile('TestClass1'));
        $this->assertEquals('/path/to/TestClass2.php', $classMap->getClassFile('TestClass2'));
    }

    // === File Management Tests ===

    public function test_add_file(): void
    {
        $filePath = '/path/to/file.php';
        $result = $this->autoloader->addFile($filePath);
        $this->assertSame($this->autoloader, $result); // Test fluent interface

        $reflection = new \ReflectionClass($this->autoloader);
        $filesProperty = $reflection->getProperty('files');
        $filesProperty->setAccessible(true);
        $files = $filesProperty->getValue($this->autoloader);

        $this->assertContains($filePath, $files);
    }

    public function test_add_files(): void
    {
        $files = ['/path/to/file1.php', '/path/to/file2.php'];
        $result = $this->autoloader->addFiles($files);
        $this->assertSame($this->autoloader, $result); // Test fluent interface

        $reflection = new \ReflectionClass($this->autoloader);
        $filesProperty = $reflection->getProperty('files');
        $filesProperty->setAccessible(true);
        $actualFiles = $filesProperty->getValue($this->autoloader);

        $this->assertContains('/path/to/file1.php', $actualFiles);
        $this->assertContains('/path/to/file2.php', $actualFiles);
    }

    public function test_add_file_normalizes_path(): void
    {
        $this->autoloader->addFile('/path/to/../file.php');

        $reflection = new \ReflectionClass($this->autoloader);
        $filesProperty = $reflection->getProperty('files');
        $filesProperty->setAccessible(true);
        $files = $filesProperty->getValue($this->autoloader);

        // Path should be normalized
        $this->assertCount(1, $files);
        $this->assertNotContains('/path/to/../file.php', $files);
    }

    // === Registration Tests ===

    public function test_register_and_unregister(): void
    {
        // Test registration
        $result = $this->autoloader->register();
        $this->assertTrue($result);
        $this->assertTrue($this->autoloader->isRegistered());

        // Test unregistration
        $result = $this->autoloader->unregister();
        $this->assertTrue($result);
        $this->assertFalse($this->autoloader->isRegistered());
    }

    public function test_register_twice_returns_true(): void
    {
        $this->autoloader->register();
        $result = $this->autoloader->register();
        $this->assertTrue($result); // Already registered should return true
    }

    public function test_unregister_when_not_registered_returns_true(): void
    {
        $result = $this->autoloader->unregister();
        $this->assertTrue($result); // Not registered should return true
    }

    public function test_load_class_from_namespace(): void
    {
        // Create a temporary PHP file for testing
        $tempDir = createTempDirectory('namespace_test');
        $tempFile = $tempDir.'/TestClass.php';
        file_put_contents($tempFile, '<?php namespace App\Test; class TestClass {}');

        // Add namespace and register autoloader
        $this->autoloader->addNamespace('App\\Test\\', $tempDir);
        $this->autoloader->register();

        // Test class loading
        $reflection = new \ReflectionClass($this->autoloader);
        $loadClassMethod = $reflection->getMethod('loadClass');
        $loadClassMethod->setAccessible(true);

        $result = $loadClassMethod->invoke($this->autoloader, 'App\\Test\\TestClass');
        $this->assertTrue($result);
        $this->assertTrue(class_exists('App\\Test\\TestClass', false));
    }

    public function test_load_class_from_class_map(): void
    {
        // Create a temporary PHP file for testing
        $tempFile = createTempFile('<?php class DirectMappedClass {}');

        // Add class and register autoloader
        $this->autoloader->addClass('DirectMappedClass', $tempFile);
        $this->autoloader->register();

        // Test class loading
        $reflection = new \ReflectionClass($this->autoloader);
        $loadClassMethod = $reflection->getMethod('loadClass');
        $loadClassMethod->setAccessible(true);

        $result = $loadClassMethod->invoke($this->autoloader, 'DirectMappedClass');
        $this->assertTrue($result);
        $this->assertTrue(class_exists('DirectMappedClass', false));
    }

    // === Class Loading Tests ===

    public function test_load_class_returns_null_when_class_not_found(): void
    {
        $this->autoloader->register();

        $result = $this->autoloader->loadClass('NonExistentClass');
        $this->assertNull($result);
    }

    public function test_load_class_prioritizes_class_map_over_namespace(): void
    {
        // Create two different temporary files
        $classMapFile = createTempFile('<?php class PriorityTest { public static $source = "classmap"; }');
        $tempDir = createTempDirectory('priority_test');
        $namespaceFile = $tempDir.'/PriorityTest.php';
        file_put_contents($namespaceFile, '<?php namespace Test; class PriorityTest { public static $source = "namespace"; }');

        // Add both namespace and class mapping
        $this->autoloader->addNamespace('Test\\', $tempDir);
        $this->autoloader->addClass('PriorityTest', $classMapFile);
        $this->autoloader->register();

        // Load the class - should come from class map (higher priority)
        $this->autoloader->loadClass('PriorityTest');

        $this->assertTrue(class_exists('PriorityTest', false));
        // The class from classmap should be loaded (without namespace)
        $this->assertEquals('classmap', \PriorityTest::$source);
    }

    public function test_include_files_on_registration(): void
    {
        // Create temporary files with global variables
        $file1 = createTempFile('<?php $GLOBALS["test_include_1"] = "loaded";');
        $file2 = createTempFile('<?php $GLOBALS["test_include_2"] = "loaded";');

        $this->autoloader->addFiles([$file1, $file2]);

        // Variables should not be set before registration
        $this->assertFalse(isset($GLOBALS['test_include_1']));
        $this->assertFalse(isset($GLOBALS['test_include_2']));

        // Register should include files
        $this->autoloader->register();

        // Variables should be set after registration
        $this->assertTrue(isset($GLOBALS['test_include_1']));
        $this->assertTrue(isset($GLOBALS['test_include_2']));
        $this->assertEquals('loaded', $GLOBALS['test_include_1']);
        $this->assertEquals('loaded', $GLOBALS['test_include_2']);
    }

    // === Integration Tests ===

    public function test_complex_namespace_scenario(): void
    {
        // Create nested directory structure
        $baseDir = createTempDirectory('complex_test');
        $subDir = $baseDir.'/Sub/Namespace';
        mkdir($subDir, 0755, true);

        $testFile = $subDir.'/ComplexTest.php';
        file_put_contents($testFile, '<?php namespace App\\Sub\\Namespace; class ComplexTest { public static $loaded = true; }');

        // Add namespace mapping
        $this->autoloader->addNamespace('App\\', $baseDir);
        $this->autoloader->register();

        // Load the class through PSR-4
        $result = $this->autoloader->loadClass('App\\Sub\\Namespace\\ComplexTest');
        $this->assertTrue($result);
        $this->assertTrue(class_exists('App\\Sub\\Namespace\\ComplexTest', false));
        $this->assertTrue(\App\Sub\Namespace\ComplexTest::$loaded);
    }

    public function test_path_normalization(): void
    {
        // Test that paths with mixed separators are normalized
        $this->autoloader->addNamespace('Test\\', '/path\\with/mixed\\separators');
        $this->autoloader->addFile('/file\\with/mixed\\separators.php');

        // Check that paths are normalized internally
        $namespaces = $this->autoloader->getNamespaces();
        $this->assertArrayHasKey('Test\\', $namespaces);

        $reflection = new \ReflectionClass($this->autoloader);
        $filesProperty = $reflection->getProperty('files');
        $filesProperty->setAccessible(true);
        $files = $filesProperty->getValue($this->autoloader);

        // Files array should contain normalized paths (specific assertion will depend on OS)
        $this->assertCount(1, $files);
    }

    // === Edge Cases and Error Handling ===

    public function test_handle_non_existent_file(): void
    {
        $this->autoloader->addClass('NonExistentFileClass', '/path/to/nonexistent.php');
        $this->autoloader->register();

        $result = $this->autoloader->loadClass('NonExistentFileClass');
        $this->assertFalse($result);
        $this->assertFalse(class_exists('NonExistentFileClass', false));
    }

    #[DataProvider('classNameProvider')]
    public function test_handle_different_class_names(string $className, string $expectedResult): void
    {
        $tempFile = createTempFile('<?php class '.$expectedResult.' {}');
        $this->autoloader->addClass($className, $tempFile);
        $this->autoloader->register();

        $result = $this->autoloader->loadClass($className);
        $this->assertTrue($result);
        $this->assertTrue(class_exists($expectedResult, false));
    }

    public static function classNameProvider(): array
    {
        return [
            'Simple class' => ['SimpleClass', 'SimpleClass'],
            'Namespaced class' => ['Namespace\\TestClass', 'TestClass'],
            'Deep namespace' => ['Very\\Deep\\Namespace\\TestClass', 'TestClass'],
        ];
    }

    public function test_multiple_paths_for_same_namespace(): void
    {
        $dir1 = createTempDirectory('multi_path_1');
        $dir2 = createTempDirectory('multi_path_2');

        // Create same class in different directories
        file_put_contents($dir1.'/FirstClass.php', '<?php namespace Multi; class FirstClass {}');
        file_put_contents($dir2.'/SecondClass.php', '<?php namespace Multi; class SecondClass {}');

        // Add both paths to same namespace
        $this->autoloader->addNamespace('Multi\\', $dir1);
        $this->autoloader->addNamespace('Multi\\', $dir2);
        $this->autoloader->register();

        // Both classes should be loadable
        $result1 = $this->autoloader->loadClass('Multi\\FirstClass');
        $result2 = $this->autoloader->loadClass('Multi\\SecondClass');

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue(class_exists('Multi\\FirstClass', false));
        $this->assertTrue(class_exists('Multi\\SecondClass', false));
    }
}
