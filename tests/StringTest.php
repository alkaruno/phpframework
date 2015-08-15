<?php

use Xplosio\PhpFramework\String;

class StringTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require '../vendor/autoload.php';
    }

    public function testStartsWith()
    {
        self::assertTrue(String::startsWith('12345', ''));
        self::assertTrue(String::startsWith('12345', '123'));
        self::assertTrue(String::startsWith('12345', '12345'));

        self::assertFalse(String::startsWith('12345', '23'));
        self::assertFalse(String::startsWith('', '23'));
    }

    public function testEndsWith()
    {
        self::assertTrue(String::endsWith('12345', ''));
        self::assertTrue(String::endsWith('12345', '345'));
        self::assertTrue(String::endsWith('12345', '12345'));

        self::assertFalse(String::startsWith('12345', '34'));
        self::assertFalse(String::startsWith('', '34'));
    }

    public function testToSnakeCase()
    {
        self::assertEquals(String::toSnakeCase('CamelCaseToSnakeCase'), 'camel_case_to_snake_case');
        self::assertEquals(String::toSnakeCase('camelCaseToSnakeCase'), 'camel_case_to_snake_case');
        self::assertEquals(String::toSnakeCase('HTML'), 'html');
        self::assertEquals(String::toSnakeCase('camel_case_to_snake_case'), 'camel_case_to_snake_case');
    }

    public function testToCamelCase()
    {
        self::assertEquals(String::toCamelCase('camel_case_to_snake_case'), 'camelCaseToSnakeCase');
        self::assertEquals(String::toCamelCase('camel_case_to_snake_case', true), 'CamelCaseToSnakeCase');

        self::assertEquals(String::toCamelCase('camelCaseToSnakeCase'), 'camelCaseToSnakeCase');
        self::assertEquals(String::toCamelCase('CamelCaseToSnakeCase'), 'CamelCaseToSnakeCase');
    }
}
