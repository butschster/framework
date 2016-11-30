<?php

use Illuminate\Foundation\Application;
use Mockery as m;
use SleepingOwl\Framework\SleepingOwl;

class SleepingOwlTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    /**
     * @param array $config
     *
     * @return Application
     */
    protected function getApplication(array $config = [])
    {
        $app = new Application();
        $app['config'] = new \Illuminate\Config\Repository([
            'sleepingowl' => [

            ]
        ] + $config);


        return $app;
    }

    public function testConstructor()
    {
        $app = m::mock(Application::class);
        $app->shouldReceive('register')->with(m::on(function ($provider) {
            if (! $provider instanceof \Illuminate\Support\ServiceProvider) {
                return false;
            }

            return true;
        }));

        $app->shouldReceive('alias');
        $app->shouldReceive('offsetGet')->andReturnUsing(function($key) {
            if ($key == 'config') {
                return new \Illuminate\Config\Repository([
                    'sleepingowl' => [

                    ]
                ]);
            }
        });

        $framework = new SleepingOwl($app);
        $this->assertEquals(SleepingOwl::VERSION, $framework->version());
    }

    public function testVersionMethod()
    {
        $framework = new SleepingOwl($this->getApplication());
        $this->assertEquals(SleepingOwl::VERSION, $framework->version());
    }

    /**
     * @covers SleepingOwl::setBasePath()
     * @covers SleepingOwl::basePath()
     *
     * @dataProvider pathsProvider
     */
    public function testSettingBasePath($path, $correctPath)
    {
        $app = $this->getApplication();
        $framework = new SleepingOwl($app, $path);

        $this->assertEquals($correctPath, $framework->basePath());
        $this->assertEquals($correctPath, $app['sleepingowl.path.base']);
    }

    public function pathsProvider()
    {
        return [
            ['folder1/folder2/', 'folder1/folder2'],
            ['folder1/folder2', 'folder1/folder2'],
            ['folder1\folder2\\', 'folder1\folder2']
        ];
    }

    public function testSettingContext()
    {
        $framework = new SleepingOwl($this->getApplication());
        $framework->setContext('test', 'test1');

        $this->assertEquals(['test', 'test1'], $framework->context());

        $framework->setContext('test');
        $this->assertEquals(['test', 'test1'], $framework->context());

        $framework->setContext('test2');
        $this->assertEquals(['test', 'test1', 'test2'], $framework->context());
    }

    public function testCheckingContext()
    {
        $framework = new SleepingOwl($this->getApplication());
        $framework->setContext('test', 'test1');

        $this->assertTrue($framework->context('test'));
        $this->assertTrue($framework->context('test1'));
        $this->assertFalse($framework->context('test2'));

        $framework->setContext('test2');
        $this->assertTrue($framework->context('test2'));

        $this->assertTrue($framework->context('test3', 'test2'));
        $this->assertFalse($framework->context('test3', 'test4'));
    }
}