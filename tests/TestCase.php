<?php

namespace Digiworld\DigiChat\Tests;

use Digiworld\DigiChat\DigiChatManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container();
        Container::setInstance($this->app);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);

        $this->app->instance('config', new Repository([
            'digichat.api_token' => 'test-token',
            'digichat.api_secret' => 'test-secret',
        ]));
        $this->app->instance('http', new HttpFactory());
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    protected function manager(): DigiChatManager
    {
        return new DigiChatManager();
    }
}
