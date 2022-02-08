<?php

namespace MkyEngine\Tests;

use MkyEngine\MkyEngine;
use PHPUnit\Framework\TestCase;
use MkyEngine\Tests\App\TestDirective;
use MkyEngine\Tests\App\TestFormatter;

class MkyEngineTest extends TestCase
{
    /**
     * @var MkyEngine
     */
    private MkyEngine $mkyEngine;

    public function setUp(): void
    {
        $config = [
            'views' => __DIR__ . '/app/views',
            'cache' => __DIR__ . '/app/cache/views'
        ];
        $this->mkyEngine = new MkyEngine($config);
    }

    public function testConfig()
    {
        $views = $this->mkyEngine->getConfig('views');
        $cache = $this->mkyEngine->getConfig('cache');

        $this->assertEquals(__DIR__ . '/app/views', $views);
        $this->assertEquals(__DIR__ . '/app/cache/views', $cache);
    }

    public function testViewsCacheFound()
    {
        $cacheFile = $this->mkyEngine->getConfig('cache');
        $cacheFile .= '/' . md5('emptyView') . '.cache.php';
        $this->mkyEngine->view('emptyView');
        $this->assertTrue(file_exists($cacheFile));
    }

    public function testNotEmptyCacheFile()
    {
        $cacheFile = $this->mkyEngine->getConfig('cache');
        $cacheFile .= '/' . md5('notEmptyView') . '.cache.php';
        $this->mkyEngine->view('notEmptyView');
        $this->assertEquals(5, file_get_contents($cacheFile));
    }

    public function testExtends()
    {
        $this->assertEquals('child layouts title2', $this->mkyEngine->view('content'));
    }


    public function testVariablePassed()
    {
        $this->assertEquals('variable: Mky', $this->mkyEngine->view('variableView', ['var' => 'Mky']));
        $this->assertEquals('variable: Mky Engine', $this->mkyEngine->view('multiVariableView', ['var' => 'Mky', 'var2' => 'Engine']));
    }

    public function testIncludeView()
    {
        $this->assertEquals('INCLUDE_VIEW', $this->mkyEngine->view('withIncludeView'));
    }

    public function testNativeDirectives()
    {
        $this->assertEquals("Else Page true - index - 5\r\n", $this->mkyEngine->view('directiveView', ['name' => 'true']));
    }

    public function testCustomDirective()
    {
        $this->mkyEngine->addDirectives(new TestDirective());
        $this->assertEquals("'Mky' test", $this->mkyEngine->view('customDirectiveView', ['var' => 'Mky']));
    }

    public function testNativeFormatters()
    {
        $vars = [
            'var' => [1,2,3],
            'var2' => 1.256,
            'var3' => 'mky',
        ];
        $this->assertEquals('3 - $1.26 - MKY', $this->mkyEngine->view('formatterView', $vars));
    }

    public function testCustomFormatter()
    {
        $this->mkyEngine->addFormatters(new TestFormatter());
        $this->assertEquals("Mky test - Mky test engine", $this->mkyEngine->view('customFormatterView', ['var' => 'Mky']));
    }
}
