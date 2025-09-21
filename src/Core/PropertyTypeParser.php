<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Core;

/**
 * Parser for property type definitions
 *
 * This class is responsible for parsing property definition strings in the format
 * "name:type,email:string" and converting them into arrays of property names and types.
 * It handles complex types including generics, union types, and fully qualified class names.
 */
final class PropertyTypeParser
{
    /**
     * Parse a property definition string into an array of property names and types
     *
     * Supports complex type definitions including:
     * - Simple types: "id:int,name:string"
     * - Fully qualified class names: "user:App\Models\User"
     * - Generic types: "items:array<Item>,metadata:array<string,mixed>"
     * - Union types: "value:string|int|null"
     *
     * @param string $propertiesString Properties in "name:type,name2:type2" format
     * @return array<string, string> Array of property name => type mappings
     */
    public static function parse(string $propertiesString): array
    {
        $properties = [];
        $current = '';
        $depth = 0;
        $inProperty = true;
        $propertyName = '';

        for ($i = 0; $i < strlen($propertiesString); $i++) {
            $char = $propertiesString[$i];

            switch ($char) {
                case '<':
                case '(':
                    $depth++;
                    $current .= $char;
                    break;
                case '>':
                case ')':
                    $depth--;
                    $current .= $char;
                    break;
                case ':':
                    if ($depth === 0 && $inProperty) {
                        $propertyName = trim($current);
                        $current = '';
                        $inProperty = false;
                    } else {
                        $current .= $char;
                    }
                    break;
                case ',':
                    if ($depth === 0) {
                        if (!empty($propertyName) && !empty(trim($current))) {
                            $properties[$propertyName] = trim($current);
                        }
                        $current = '';
                        $inProperty = true;
                        $propertyName = '';
                    } else {
                        $current .= $char;
                    }
                    break;
                default:
                    $current .= $char;
            }
        }

        // Add the last property
        if (!empty($propertyName) && !empty(trim($current))) {
            $properties[$propertyName] = trim($current);
        }

        return $properties;
    }
}
