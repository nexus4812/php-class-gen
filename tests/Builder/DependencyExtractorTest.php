<?php

declare(strict_types=1);

namespace Tests\Builder;

use PHPUnit\Framework\TestCase;
use PhpGen\ClassGenerator\Builder\DependencyExtractor;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\TraitType;
use Nette\PhpGenerator\Method;

final class DependencyExtractorTest extends TestCase
{
    private DependencyExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new DependencyExtractor();
    }

    public function testExtractDependenciesFromEmptyClass(): void
    {
        // Arrange
        $class = new ClassType('TestClass');

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $this->assertSame([], $dependencies);
    }

    public function testExtractDependenciesFromClassWithExtends(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $class->setExtends('App\\Base\\BaseClass');

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Base\\BaseClass'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromClassWithImplements(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $class->setImplements(['App\\Contracts\\FirstInterface', 'App\\Contracts\\SecondInterface']);

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Contracts\\FirstInterface', 'App\\Contracts\\SecondInterface'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromClassWithTraits(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $class->addTrait('App\\Traits\\FirstTrait');
        $class->addTrait('App\\Traits\\SecondTrait');

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Traits\\FirstTrait', 'App\\Traits\\SecondTrait'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromInterfaceWithExtends(): void
    {
        // Arrange
        $interface = new InterfaceType('TestInterface');
        $interface->setExtends(['App\\Contracts\\BaseInterface', 'App\\Contracts\\AnotherInterface']);

        // Act
        $dependencies = $this->extractor->extractDependencies($interface);

        // Assert
        $expected = ['App\\Contracts\\BaseInterface', 'App\\Contracts\\AnotherInterface'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromTraitWithTraits(): void
    {
        // Arrange
        $trait = new TraitType('TestTrait');
        $trait->addTrait('App\\Traits\\HelperTrait');

        // Act
        $dependencies = $this->extractor->extractDependencies($trait);

        // Assert
        $expected = ['App\\Traits\\HelperTrait'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromMethodParameters(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $method = $class->addMethod('testMethod');
        $method->addParameter('request')->setType('App\\Http\\Request');
        $method->addParameter('service')->setType('App\\Services\\UserService');
        $method->addParameter('id')->setType('int'); // Built-in type, should not be included

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Http\\Request', 'App\\Services\\UserService'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromMethodReturnTypes(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $method1 = $class->addMethod('getUser');
        $method1->setReturnType('App\\Models\\User');

        $method2 = $class->addMethod('getCount');
        $method2->setReturnType('int'); // Built-in type, should not be included

        $method3 = $class->addMethod('getCollection');
        $method3->setReturnType('Illuminate\\Support\\Collection');

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Models\\User', 'Illuminate\\Support\\Collection'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromProperties(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $class->addProperty('user')->setType('App\\Models\\User');
        $class->addProperty('name')->setType('string'); // Built-in type, should not be included
        $class->addProperty('service')->setType('App\\Services\\EmailService');

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Models\\User', 'App\\Services\\EmailService'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromAttributes(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $class->addAttribute('App\\Attributes\\Route', ['/api/users']);
        $class->addAttribute('App\\Attributes\\Middleware', ['auth']);

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Attributes\\Route', 'App\\Attributes\\Middleware'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesIgnoresBuiltInTypes(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $method = $class->addMethod('testMethod');
        $method->addParameter('name')->setType('string');
        $method->addParameter('age')->setType('int');
        $method->addParameter('active')->setType('bool');
        $method->setReturnType('array');

        $class->addProperty('count')->setType('int');
        $class->addProperty('data')->setType('array');

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $this->assertSame([], $dependencies);
    }

    public function testExtractDependenciesIgnoresNonQualifiedClassNames(): void
    {
        // Arrange
        $class = new ClassType('TestClass');
        $method = $class->addMethod('testMethod');
        $method->addParameter('helper')->setType('Helper'); // No namespace
        $method->setReturnType('Response'); // No namespace

        $class->addProperty('model')->setType('Model'); // No namespace

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $this->assertSame([], $dependencies);
    }

    public function testExtractDependenciesRemovesDuplicates(): void
    {
        // Arrange
        $class = new ClassType('TestClass');

        // Add the same dependency multiple times in different contexts
        $class->setImplements(['App\\Contracts\\UserInterface']);
        $class->addProperty('user')->setType('App\\Models\\User');

        $method1 = $class->addMethod('getUser');
        $method1->setReturnType('App\\Models\\User'); // Duplicate

        $method2 = $class->addMethod('setUser');
        $method2->addParameter('user')->setType('App\\Models\\User'); // Duplicate

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = ['App\\Contracts\\UserInterface', 'App\\Models\\User'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromComplexClass(): void
    {
        // Arrange
        $class = new ClassType('UserController');
        $class->setExtends('App\\Http\\Controllers\\Controller');
        $class->setImplements(['App\\Contracts\\UserControllerInterface']);
        $class->addTrait('App\\Traits\\ValidationTrait');

        // Add properties
        $class->addProperty('userService')->setType('App\\Services\\UserService');
        $class->addProperty('repository')->setType('App\\Repositories\\UserRepository');

        // Add method with parameters and return type
        $method = $class->addMethod('store');
        $method->addParameter('request')->setType('App\\Http\\Requests\\CreateUserRequest');
        $method->addParameter('validator')->setType('Illuminate\\Validation\\Validator');
        $method->setReturnType('Illuminate\\Http\\JsonResponse');

        // Add attributes
        $class->addAttribute('App\\Attributes\\Route', ['/users']);

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $expected = [
            'App\\Http\\Controllers\\Controller',
            'App\\Contracts\\UserControllerInterface',
            'App\\Traits\\ValidationTrait',
            'App\\Http\\Requests\\CreateUserRequest',
            'Illuminate\\Validation\\Validator',
            'Illuminate\\Http\\JsonResponse',
            'App\\Services\\UserService',
            'App\\Repositories\\UserRepository',
            'App\\Attributes\\Route',
        ];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFiltersEmptyValues(): void
    {
        // Arrange - Create a class with both valid and potentially empty dependencies
        $class = new ClassType('TestClass');
        $class->setExtends('App\\Base\\BaseClass'); // Valid dependency

        $method = $class->addMethod('testMethod');
        $method->addParameter('validParam')->setType('App\\Services\\TestService'); // Valid dependency
        $method->addParameter('builtInParam')->setType('string'); // Should be filtered (no namespace)

        // Act
        $dependencies = $this->extractor->extractDependencies($class);

        // Assert
        $this->assertNotEmpty($dependencies, 'Should have some dependencies');
        foreach ($dependencies as $dependency) {
            $this->assertNotEmpty($dependency, 'Dependencies should not contain empty values');
            $this->assertIsString($dependency, 'Dependencies should be strings');
            $this->assertStringContainsString('\\', $dependency, 'Dependencies should be fully qualified class names');
        }

        // Verify specific expected dependencies
        $expected = ['App\\Base\\BaseClass', 'App\\Services\\TestService'];
        $this->assertSame($expected, $dependencies);
    }

    public function testExtractDependenciesFromInterfaceWithMethods(): void
    {
        // Arrange
        $interface = new InterfaceType('UserRepositoryInterface');
        $interface->setExtends(['App\\Contracts\\BaseRepositoryInterface']);

        $method = $interface->addMethod('findById');
        $method->addParameter('id')->setType('int');
        $method->setReturnType('App\\Models\\User');

        // Act
        $dependencies = $this->extractor->extractDependencies($interface);

        // Assert
        $expected = ['App\\Contracts\\BaseRepositoryInterface', 'App\\Models\\User'];
        $this->assertSame($expected, $dependencies);
    }
}
