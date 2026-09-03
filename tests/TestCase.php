<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected string $testingDatabasePath;

    public function createApplication()
    {
        $testingDatabasePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'prawkonaraz-testing-'.bin2hex(random_bytes(8)).'.sqlite';

        touch($testingDatabasePath);
        $this->testingDatabasePath = $testingDatabasePath;

        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv("DB_DATABASE={$testingDatabasePath}");

        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $testingDatabasePath;

        $_SERVER['APP_ENV'] = 'testing';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $testingDatabasePath;

        $app = parent::createApplication();

        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', $testingDatabasePath);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.connections.sqlite.database', $this->testingDatabasePath);
        $this->app['config']->set('study.pjm_module_enabled', true);
        $this->app['db']->purge('sqlite');
        $this->artisan('migrate', ['--force' => true]);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        if (isset($this->app)) {
            DB::disconnect('sqlite');
            DB::purge('sqlite');
        }

        parent::tearDown();

        if (isset($this->testingDatabasePath) && file_exists($this->testingDatabasePath)) {
            clearstatcache(true, $this->testingDatabasePath);
            @unlink($this->testingDatabasePath);
        }
    }
}
