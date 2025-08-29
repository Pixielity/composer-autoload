<?php

declare(strict_types=1);

namespace Modules\ComposerAutoload\Tests\Unit\Services;

use Modules\ComposerAutoload\Interfaces\ClassMapInterface;
use Modules\ComposerAutoload\Services\ClassMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassMap::class)]
class ClassMapTest extends TestCase
{
    private ClassMap $classMap;

    protected function setUp(): void
    {
        $this->classMap = new ClassMap;
    }

    protected function tearDown(): void
    {
        cleanupTempFiles();
    }

    public function test_implements_class_map_interface(): void
    {
        $this->assertInstanceOf(ClassMapInterface::class, $this->classMap);
    }

    public function test_can_be_constructed(): void
    {
        $this->assertInstanceOf(ClassMap::class, $this->classMap);
    }

    // === Construction Tests ===

    public function test_can_be_constructed_with_initial_class_map(): void
    {
        $initialClasses = [
            'InitialClass1' => '/path/to/InitialClass1.php',
            'InitialClass2' => '/path/to/InitialClass2.php',
        ];

        $classMap = new ClassMap($initialClasses);

        $this->assertTrue($classMap->hasClass('InitialClass1'));
        $this->assertTrue($classMap->hasClass('InitialClass2'));
        $this->assertEquals('/path/to/InitialClass1.php', $classMap->getClassFile('InitialClass1'));
        $this->assertEquals('/path/to/InitialClass2.php', $classMap->getClassFile('InitialClass2'));
    }

    // === Basic Class Management Tests ===

    public function test_add_single_class(): void
    {
        $result = $this->classMap->addClass('TestClass', '/path/to/TestClass.php');
        $this->assertSame($this->classMap, $result); // Test fluent interface

        $this->assertTrue($this->classMap->hasClass('TestClass'));
        $this->assertEquals('/path/to/TestClass.php', $this->classMap->getClassFile('TestClass'));
    }

    public function test_add_multiple_classes(): void
    {
        $classes = [
            'Class1' => '/path/to/Class1.php',
            'Class2' => '/path/to/Class2.php',
            'Class3' => '/path/to/Class3.php',
        ];

        $result = $this->classMap->addClasses($classes);
        $this->assertSame($this->classMap, $result); // Test fluent interface

        foreach ($classes as $className => $filePath) {
            $this->assertTrue($this->classMap->hasClass($className));
            $this->assertEquals($filePath, $this->classMap->getClassFile($className));
        }
    }

    public function test_get_class_file_returns_null_for_non_existent_class(): void
    {
        $result = $this->classMap->getClassFile('NonExistentClass');
        $this->assertNull($result);
    }

    public function test_has_class_returns_false_for_non_existent_class(): void
    {
        $this->assertFalse($this->classMap->hasClass('NonExistentClass'));
    }

    public function test_get_all_classes_returns_all_classes(): void
    {
        $classes = [
            'Class1' => '/path/to/Class1.php',
            'Class2' => '/path/to/Class2.php',
        ];

        $this->classMap->addClasses($classes);
        $result = $this->classMap->getAllClasses();

        $this->assertEquals($classes, $result);
    }

    public function test_get_all_classes_returns_empty_array_when_empty(): void
    {
        $result = $this->classMap->getAllClasses();
        $this->assertEquals([], $result);
    }

    public function test_remove_class(): void
    {
        $this->classMap->addClass('TestClass', '/path/to/TestClass.php');
        $this->assertTrue($this->classMap->hasClass('TestClass'));

        $result = $this->classMap->removeClass('TestClass');
        $this->assertSame($this->classMap, $result); // Test fluent interface
        $this->assertFalse($this->classMap->hasClass('TestClass'));
        $this->assertNull($this->classMap->getClassFile('TestClass'));
    }

    public function test_remove_non_existent_class_does_nothing(): void
    {
        // Should not throw any exception
        $result = $this->classMap->removeClass('NonExistentClass');
        $this->assertSame($this->classMap, $result); // Test fluent interface
    }

    public function test_clear_classes(): void
    {
        $classes = [
            'Class1' => '/path/to/Class1.php',
            'Class2' => '/path/to/Class2.php',
        ];

        $this->classMap->addClasses($classes);
        $this->assertCount(2, $this->classMap->getAllClasses());

        $result = $this->classMap->clearClasses();
        $this->assertSame($this->classMap, $result); // Test fluent interface
        $this->assertCount(0, $this->classMap->getAllClasses());
    }

    public function test_overwrite_existing_class(): void
    {
        $this->classMap->addClass('TestClass', '/path/to/original.php');
        $this->assertEquals('/path/to/original.php', $this->classMap->getClassFile('TestClass'));

        $this->classMap->addClass('TestClass', '/path/to/overridden.php');
        $this->assertEquals('/path/to/overridden.php', $this->classMap->getClassFile('TestClass'));
    }

    // === Path Normalization Tests ===

    public function test_path_normalization(): void
    {
        // Test that paths are normalized (backslashes converted to forward slashes or directory separators)
        $this->classMap->addClass('TestClass', '/path\\to\\TestClass.php');

        $filePath = $this->classMap->getClassFile('TestClass');
        // Should be normalized to use directory separator
        $this->assertStringNotContains('\\\\', $filePath);
        $this->assertTrue($this->classMap->hasClass('TestClass'));
    }

    #[DataProvider('classNameProvider')]
    public function test_class_names_case_handling(string $className1, string $className2, bool $shouldBeDifferent): void
    {
        $this->classMap->addClass($className1, '/path/to/file1.php');
        $this->classMap->addClass($className2, '/path/to/file2.php');

        $this->assertTrue($this->classMap->hasClass($className1));
        $this->assertTrue($this->classMap->hasClass($className2));

        if ($shouldBeDifferent) {
            $this->assertNotEquals($this->classMap->getClassFile($className1), $this->classMap->getClassFile($className2));
        } else {
            $this->assertEquals($this->classMap->getClassFile($className1), $this->classMap->getClassFile($className2));
        }
    }

    public static function classNameProvider(): array
    {
        return [
            'Case sensitive - different case' => ['TestClass', 'testclass', true],
            'Case sensitive - same case' => ['TestClass', 'TestClass', false],
            'Namespace handling' => ['Namespace\\TestClass', 'Namespace\\TestClass', false],
        ];
    }

    // === Edge Cases and Error Handling ===

    public function test_empty_class_name(): void
    {
        $this->classMap->addClass('', '/path/to/empty.php');

        $this->assertTrue($this->classMap->hasClass(''));
        $this->assertEquals('/path/to/empty.php', $this->classMap->getClassFile(''));
    }

    public function test_class_name_with_special_characters(): void
    {
        $specialClassName = 'Test\\Namespace\\Class$WithSpecial-Characters';
        $this->classMap->addClass($specialClassName, '/path/to/special.php');

        $this->assertTrue($this->classMap->hasClass($specialClassName));
        $this->assertEquals('/path/to/special.php', $this->classMap->getClassFile($specialClassName));
    }

    public function test_large_class_map(): void
    {
        // Test performance with large number of classes
        $classes = [];
        for ($i = 0; $i < 1000; $i++) {
            $classes["Class{$i}"] = "/path/to/Class{$i}.php";
        }

        $this->classMap->addClasses($classes);

        // Test random access
        $this->assertTrue($this->classMap->hasClass('Class500'));
        $this->assertEquals('/path/to/Class500.php', $this->classMap->getClassFile('Class500'));

        // Test count
        $this->assertCount(1000, $this->classMap->getAllClasses());
    }

    public function test_empty_path(): void
    {
        $this->classMap->addClass('EmptyPathClass', '');

        $this->assertTrue($this->classMap->hasClass('EmptyPathClass'));
        $this->assertEquals('', $this->classMap->getClassFile('EmptyPathClass'));
    }
}
