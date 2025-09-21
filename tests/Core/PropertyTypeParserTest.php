<?php

declare(strict_types=1);

namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use PhpGen\ClassGenerator\Core\PropertyTypeParser;

final class PropertyTypeParserTest extends TestCase
{
    public function testParseSimpleTypes(): void
    {
        // Arrange
        $input = 'id:int,name:string,active:bool';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'id' => 'int',
            'name' => 'string',
            'active' => 'bool',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseWithSpaces(): void
    {
        // Arrange
        $input = 'id : int , name : string , active : bool';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'id' => 'int',
            'name' => 'string',
            'active' => 'bool',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseFullyQualifiedClassNames(): void
    {
        // Arrange
        $input = 'user:App\\Models\\User,order:App\\Models\\Order';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'user' => 'App\\Models\\User',
            'order' => 'App\\Models\\Order',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseGenericTypes(): void
    {
        // Arrange
        $input = 'items:array<Item>,metadata:array<string,mixed>';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'items' => 'array<Item>',
            'metadata' => 'array<string,mixed>',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseComplexGenericTypes(): void
    {
        // Arrange
        $input = 'users:array<App\\Models\\User>,config:array<string,array<string,mixed>>';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'users' => 'array<App\\Models\\User>',
            'config' => 'array<string,array<string,mixed>>',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseUnionTypes(): void
    {
        // Arrange
        $input = 'value:string|int|null,status:active|inactive';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'value' => 'string|int|null',
            'status' => 'active|inactive',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseNestedParentheses(): void
    {
        // Arrange
        $input = 'callback:callable(string,int):bool,complex:Closure(array<string>):void';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'callback' => 'callable(string,int):bool',
            'complex' => 'Closure(array<string>):void',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseMixedComplexTypes(): void
    {
        // Arrange
        $input = 'id:int,user:App\\Models\\User,items:array<App\\Models\\Item>,meta:array<string,mixed>,value:string|int|null';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'id' => 'int',
            'user' => 'App\\Models\\User',
            'items' => 'array<App\\Models\\Item>',
            'meta' => 'array<string,mixed>',
            'value' => 'string|int|null',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseEmptyString(): void
    {
        // Arrange
        $input = '';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $this->assertSame([], $result);
    }

    public function testParseSingleProperty(): void
    {
        // Arrange
        $input = 'id:int';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = ['id' => 'int'];
        $this->assertSame($expected, $result);
    }

    public function testParseInvalidFormat(): void
    {
        // Arrange
        $input = 'id:int,invalid,name:string'; // Properties without type should be ignored

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'id' => 'int',
            'name' => 'string',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseEmptyPropertyName(): void
    {
        // Arrange
        $input = ':int,name:string'; // Empty property names should be ignored

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'name' => 'string',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseEmptyPropertyType(): void
    {
        // Arrange
        $input = 'id:,name:string'; // Empty property types should be ignored

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'name' => 'string',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseTrailingComma(): void
    {
        // Arrange
        $input = 'id:int,name:string,';

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'id' => 'int',
            'name' => 'string',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseColonInTypeName(): void
    {
        // Arrange
        $input = 'callback:callable():void,id:int'; // Colons within type definitions should be handled correctly

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'callback' => 'callable():void',
            'id' => 'int',
        ];
        $this->assertSame($expected, $result);
    }

    public function testParseNestedGenericsWithCommas(): void
    {
        // Arrange
        $input = 'map:array<string,array<int,string>>,simple:int'; // Commas within nested generics should not be treated as property separators

        // Act
        $result = PropertyTypeParser::parse($input);

        // Assert
        $expected = [
            'map' => 'array<string,array<int,string>>',
            'simple' => 'int',
        ];
        $this->assertSame($expected, $result);
    }
}
