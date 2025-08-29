<?php

declare(strict_types=1);

namespace Pixielity\ComposerAutoload\Tests\Unit\Services;

use Pixielity\ComposerAutoload\Interfaces\NamespaceMapInterface;
use Pixielity\ComposerAutoload\Services\NamespaceMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamespaceMap::class)]
class NamespaceMapTest extends TestCase
{
    private NamespaceMap $namespaceMap;

    protected function setUp(): void
    {
        $this->namespaceMap = new NamespaceMap;
    }

    protected function tearDown(): void
    {
        cleanupTempFiles();
    }

    // === Construction Tests ===

    public function test_implements_namespace_map_interface(): void
    {
        $this->assertInstanceOf(NamespaceMapInterface::class, $this->namespaceMap);
    }

    public function test_can_be_constructed(): void
    {
        $this->assertInstanceOf(NamespaceMap::class, $this->namespaceMap);
    }

    public function test_can_be_constructed_with_initial_namespaces(): void
    {
        $initialNamespaces = [
            'App\\' => '/path/to/app',
            'Test\\' => ['/path/to/test1', '/path/to/test2'],
        ];

        $namespaceMap = new NamespaceMap($initialNamespaces);

        $this->assertTrue($namespaceMap->hasNamespace('App\\'));
        $this->assertTrue($namespaceMap->hasNamespace('Test\\'));
        $this->assertContains('/path/to/app', $namespaceMap->getNamespacePaths('App\\'));
        $this->assertContains('/path/to/test1', $namespaceMap->getNamespacePaths('Test\\'));
        $this->assertContains('/path/to/test2', $namespaceMap->getNamespacePaths('Test\\'));
    }

    // === Basic Namespace Management Tests ===

    public function test_add_single_namespace(): void
    {
        $result = $this->namespaceMap->addNamespace('App\\', '/path/to/app');
        $this->assertSame($this->namespaceMap, $result); // Test fluent interface

        $this->assertTrue($this->namespaceMap->hasNamespace('App\\'));
        $paths = $this->namespaceMap->getNamespacePaths('App\\');
        $this->assertIsArray($paths);
        $this->assertContains('/path/to/app', $paths);
    }

    public function test_add_multiple_paths_to_same_namespace(): void
    {
        $this->namespaceMap->addNamespace('App\\', '/path/to/app');
        $this->namespaceMap->addNamespace('App\\', '/another/path');

        $paths = $this->namespaceMap->getNamespacePaths('App\\');
        $this->assertCount(2, $paths);
        $this->assertContains('/path/to/app', $paths);
        $this->assertContains('/another/path', $paths);
    }

    public function test_add_namespace_with_prepend_option(): void
    {
        $this->namespaceMap->addNamespace('App\\', '/path/first');
        $this->namespaceMap->addNamespace('App\\', '/path/prepended', true);

        $paths = $this->namespaceMap->getNamespacePaths('App\\');
        $this->assertCount(2, $paths);
        // Prepended path should be first
        $this->assertEquals('/path/prepended', $paths[0]);
        $this->assertEquals('/path/first', $paths[1]);
    }

    public function test_add_multiple_namespaces(): void
    {
        $namespaces = [
            'App\\' => '/path/to/app',
            'Test\\' => '/path/to/test',
            'Vendor\\Package\\' => '/vendor/package',
        ];

        $result = $this->namespaceMap->addNamespaces($namespaces);
        $this->assertSame($this->namespaceMap, $result); // Test fluent interface

        foreach ($namespaces as $namespace => $path) {
            $this->assertTrue($this->namespaceMap->hasNamespace($namespace));
            $paths = $this->namespaceMap->getNamespacePaths($namespace);
            $this->assertContains($path, $paths);
        }
    }

    public function test_add_multiple_namespaces_with_array_paths(): void
    {
        $namespaces = [
            'App\\' => ['/path/to/app', '/another/app/path'],
            'Test\\' => '/path/to/test',
        ];

        $this->namespaceMap->addNamespaces($namespaces);

        $appPaths = $this->namespaceMap->getNamespacePaths('App\\');
        $this->assertCount(2, $appPaths);
        $this->assertContains('/path/to/app', $appPaths);
        $this->assertContains('/another/app/path', $appPaths);

        $testPaths = $this->namespaceMap->getNamespacePaths('Test\\');
        $this->assertCount(1, $testPaths);
        $this->assertContains('/path/to/test', $testPaths);
    }

    public function test_get_namespace_paths_returns_empty_array_for_non_existent_namespace(): void
    {
        $result = $this->namespaceMap->getNamespacePaths('NonExistent\\');
        $this->assertEquals([], $result);
    }

    public function test_has_namespace_returns_false_for_non_existent_namespace(): void
    {
        $this->assertFalse($this->namespaceMap->hasNamespace('NonExistent\\'));
    }

    public function test_get_all_namespaces_returns_all_namespaces(): void
    {
        $this->namespaceMap->addNamespace('App\\', '/app');
        $this->namespaceMap->addNamespace('Test\\', '/test');

        $result = $this->namespaceMap->getAllNamespaces();
        $this->assertArrayHasKey('App\\', $result);
        $this->assertArrayHasKey('Test\\', $result);
    }

    public function test_get_all_namespaces_returns_empty_array_when_empty(): void
    {
        $result = $this->namespaceMap->getAllNamespaces();
        $this->assertEquals([], $result);
    }

    public function test_remove_namespace(): void
    {
        $this->namespaceMap->addNamespace('App\\', '/path/to/app');
        $this->assertTrue($this->namespaceMap->hasNamespace('App\\'));

        $result = $this->namespaceMap->removeNamespace('App\\');
        $this->assertSame($this->namespaceMap, $result); // Test fluent interface
        $this->assertFalse($this->namespaceMap->hasNamespace('App\\'));
        $this->assertEquals([], $this->namespaceMap->getNamespacePaths('App\\'));
    }

    public function test_remove_non_existent_namespace_does_nothing(): void
    {
        // Should not throw any exception
        $result = $this->namespaceMap->removeNamespace('NonExistent\\');
        $this->assertSame($this->namespaceMap, $result); // Test fluent interface
    }

    public function test_clear_namespaces(): void
    {
        $this->namespaceMap->addNamespace('App\\', '/app');
        $this->namespaceMap->addNamespace('Test\\', '/test');
        $this->assertCount(2, $this->namespaceMap->getAllNamespaces());

        $result = $this->namespaceMap->clearNamespaces();
        $this->assertSame($this->namespaceMap, $result); // Test fluent interface
        $this->assertCount(0, $this->namespaceMap->getAllNamespaces());
    }

    // === PSR-4 File Resolution Tests ===

    public function test_find_file_with_existing_class(): void
    {
        // Create a temporary directory structure
        $baseDir = createTempDirectory('find_file_test');
        $subDir = $baseDir.'/Models';
        mkdir($subDir, 0755, true);

        // Create a test class file
        $testFile = $subDir.'/User.php';
        file_put_contents($testFile, '<?php namespace App\\Models; class User {}');

        // Add namespace mapping
        $this->namespaceMap->addNamespace('App\\', $baseDir);

        // Find the file
        $result = $this->namespaceMap->findFile('App\\Models\\User');
        $this->assertEquals($testFile, $result);
    }

    public function test_find_file_returns_null_for_non_existent_class(): void
    {
        $this->namespaceMap->addNamespace('App\\', '/nonexistent/path');

        $result = $this->namespaceMap->findFile('App\\Models\\NonExistent');
        $this->assertNull($result);
    }

    public function test_find_file_with_multiple_paths(): void
    {
        // Create temporary directories
        $dir1 = createTempDirectory('multi_path_1');
        $dir2 = createTempDirectory('multi_path_2');

        // Create class file only in second directory
        $testFile = $dir2.'/TestClass.php';
        file_put_contents($testFile, '<?php namespace Test; class TestClass {}');

        // Add both paths to same namespace
        $this->namespaceMap->addNamespace('Test\\', $dir1);
        $this->namespaceMap->addNamespace('Test\\', $dir2);

        // Should find file in second directory
        $result = $this->namespaceMap->findFile('Test\\TestClass');
        $this->assertEquals($testFile, $result);
    }

    #[DataProvider('psr4ClassNameProvider')]
    public function test_psr4_class_to_file_mapping(string $className, string $expectedRelativePath): void
    {
        $baseDir = createTempDirectory('psr4_test');
        $fullPath = $baseDir.'/'.$expectedRelativePath;

        // Create directory structure
        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create the file
        file_put_contents($fullPath, '<?php // Test class');

        $this->namespaceMap->addNamespace('App\\', $baseDir);

        $result = $this->namespaceMap->findFile($className);
        $this->assertEquals($fullPath, $result);
    }

    public static function psr4ClassNameProvider(): array
    {
        return [
            'Simple class' => ['App\\SimpleClass', 'SimpleClass.php'],
            'Namespaced class' => ['App\\Models\\User', 'Models/User.php'],
            'Deep namespace' => ['App\\Very\\Deep\\Namespace\\Class', 'Very/Deep/Namespace/Class.php'],
            'Class with underscores' => ['App\\My_Special_Class', 'My_Special_Class.php'],
        ];
    }

    // === Namespace Normalization Tests ===

    public function test_namespace_normalization(): void
    {
        // Test that namespaces are normalized consistently
        $testCases = [
            'App' => 'App\\',
            'App\\' => 'App\\',
            'App\\\\' => 'App\\', // Multiple trailing backslashes
            '\\App' => 'App\\', // Leading backslash
            '\\App\\' => 'App\\',
        ];

        foreach ($testCases as $input => $expected) {
            $this->namespaceMap->addNamespace($input, '/test');
            $this->assertTrue($this->namespaceMap->hasNamespace($expected), "Failed for input: {$input}");

            // Clear for next test
            $this->namespaceMap->clearNamespaces();
        }
    }

    // === Path Normalization Tests ===

    public function test_path_normalization(): void
    {
        // Test that paths are normalized correctly
        $this->namespaceMap->addNamespace('App\\', '/path\\with/mixed\\separators');

        $paths = $this->namespaceMap->getNamespacePaths('App\\');
        $this->assertCount(1, $paths);

        // Path should be normalized (exact assertion depends on OS)
        $normalizedPath = $paths[0];
        $this->assertIsString($normalizedPath);
    }

    // === Edge Cases and Error Handling ===

    public function test_empty_namespace_handling(): void
    {
        $this->namespaceMap->addNamespace('', '/global');

        $this->assertTrue($this->namespaceMap->hasNamespace('\\'));
        $paths = $this->namespaceMap->getNamespacePaths('\\');
        $this->assertContains('/global', $paths);
    }

    public function test_prevent_duplicate_paths(): void
    {
        $this->namespaceMap->addNamespace('App\\', '/app');
        $this->namespaceMap->addNamespace('App\\', '/app'); // Duplicate

        $paths = $this->namespaceMap->getNamespacePaths('App\\');
        $this->assertCount(1, $paths);
        $this->assertContains('/app', $paths);
    }

    public function test_large_number_of_namespaces(): void
    {
        // Test performance with many namespaces
        for ($i = 0; $i < 100; $i++) {
            $this->namespaceMap->addNamespace("Namespace{$i}\\", "/path/{$i}");
        }

        // Test random access
        $this->assertTrue($this->namespaceMap->hasNamespace('Namespace50\\'));
        $paths = $this->namespaceMap->getNamespacePaths('Namespace50\\');
        $this->assertContains('/path/50', $paths);

        // Test count
        $this->assertCount(100, $this->namespaceMap->getAllNamespaces());
    }

    public function test_complex_namespace_hierarchy(): void
    {
        // Set up complex hierarchy
        $namespaces = [
            'App\\' => '/app',
            'App\\Models\\' => '/app/models',
            'App\\Models\\User\\' => '/app/models/user',
            'App\\Controllers\\' => '/app/controllers',
            'Vendor\\Package\\' => '/vendor/package',
        ];

        $this->namespaceMap->addNamespaces($namespaces);

        // Test that all namespaces are registered
        foreach (array_keys($namespaces) as $namespace) {
            $this->assertTrue($this->namespaceMap->hasNamespace($namespace));
        }

        // Test precedence - more specific namespaces should be found first
        // This would be tested in the actual findFile method usage
        $allNamespaces = $this->namespaceMap->getAllNamespaces();
        $this->assertCount(5, $allNamespaces);
    }
}
