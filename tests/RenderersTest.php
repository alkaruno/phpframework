<?php

use Xplosio\PhpFramework\App;

class RenderersTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require '../vendor/autoload.php';
    }

    protected function setUp()
    {
        App::$config = [
            'views' => [
                'renderers' => [
                    'twig' => [
                        'class' => \Xplosio\PhpFramework\View\TwigViewRenderer::class
                    ],
                    'tpl' => [
                        'class' => \Xplosio\PhpFramework\View\SmartyViewRenderer::class
                    ]
                ],
                'views_path' => __DIR__ . '/resources/views'
            ]
        ];
    }

    protected function tearDown()
    {
        exec('rm -rf ' . escapeshellarg(__DIR__ . '/resources/views/cache'));
    }

    public function testPhp()
    {
        $this->assertContent('test.php', '-123-abc-');
    }

    public function testJson()
    {
        $this->assertContent('test.json', json_encode([
            'value1' => '123',
            'value2' => 'abc'
        ]));
    }

    public function testSmarty()
    {
        $this->assertContent('test.tpl', '-123-abc-');
    }

    public function testTwig()
    {
        $this->assertContent('test.twig', '-123-abc-');
    }

    private function assertContent($view, $actual)
    {
        $content = App::render($view, [
            'value1' => '123',
            'value2' => 'abc'
        ], true);

        self::assertEquals(trim($content), $actual);
    }
}
