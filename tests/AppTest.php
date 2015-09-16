<?php

use Xplosio\PhpFramework\App;
use Xplosio\PhpFramework\Logger;

class AppTest extends PHPUnit_Framework_TestCase
{
    private $routes;

    public static function setUpBeforeClass()
    {
        require '../vendor/autoload.php';
    }

    protected function setUp()
    {
        $this->routes = [
            ['^/$', 'Home'],
            ['^/charts', 'Charts'],
        ];

        App::$config = [
            'name' => 'default',
            'logging' => [
                'level' => Logger::ALERT,
                'deeper' => [
                    'much_deeper' => 42
                ]
            ]
        ];
    }

    public function testGetConfigValue()
    {
        self::assertEquals(App::getConfigValue('name'), 'default');
        self::assertEquals(App::getConfigValue(['logging', 'level']), Logger::ALERT);
        self::assertEquals(App::getConfigValue(['logging', 'deeper', 'much_deeper']), 42);
        self::assertEquals(App::getConfigValue(['logging', 'deeper', 'much_deeper_much'], 21), 21);
        self::assertEquals(App::getConfigValue(['logging_1', 'deeper', 'much_deeper_1'], 21), 21);
    }

    /**
     * @dataProvider routesProvider
     */
    public function testRoute($input, $expected)
    {
        $result = App::route($input, $this->routes);
        self::assertTrue(is_array($result) && count($result) === 2);
        self::assertEquals($result[0], $expected);
    }

    public function routesProvider()
    {
        return [
            ['/', 'Home'],
            ['/charts', 'Charts'],
            ['/charts/42', 'Charts']
        ];
    }
}
