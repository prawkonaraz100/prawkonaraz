<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class PostgresTestCase extends BaseTestCase
{
    public function createApplication()
    {
        if ($this->postgresEnabled()) {
            $this->setEnvironment('DB_CONNECTION', 'pgsql');
            $this->setEnvironment('DB_URL', '');
            $this->setEnvironment('DB_HOST', $this->postgresEnv('NEWSROOM_PG_HOST', '127.0.0.1'));
            $this->setEnvironment('DB_PORT', $this->postgresEnv('NEWSROOM_PG_PORT', '5432'));
            $this->setEnvironment('DB_DATABASE', $this->postgresEnv('NEWSROOM_PG_DATABASE', 'prawkonaraz_newsroom'));
            $this->setEnvironment('DB_USERNAME', $this->postgresEnv('NEWSROOM_PG_USERNAME', 'postgres'));
            $this->setEnvironment('DB_PASSWORD', $this->postgresEnv('NEWSROOM_PG_PASSWORD', 'postgres'));
        }

        $app = parent::createApplication();

        if ($this->postgresEnabled()) {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql.url', null);
            $app['config']->set('database.connections.pgsql.host', $this->postgresEnv('NEWSROOM_PG_HOST', '127.0.0.1'));
            $app['config']->set('database.connections.pgsql.port', $this->postgresEnv('NEWSROOM_PG_PORT', '5432'));
            $app['config']->set('database.connections.pgsql.database', $this->postgresEnv('NEWSROOM_PG_DATABASE', 'prawkonaraz_newsroom'));
            $app['config']->set('database.connections.pgsql.username', $this->postgresEnv('NEWSROOM_PG_USERNAME', 'postgres'));
            $app['config']->set('database.connections.pgsql.password', $this->postgresEnv('NEWSROOM_PG_PASSWORD', 'postgres'));
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->postgresEnabled()) {
            $this->markTestSkipped('Set NEWSROOM_POSTGRES_TESTS=1 to run PostgreSQL newsroom migration tests.');
        }

        $this->app['db']->purge('pgsql');
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        if ($this->postgresEnabled()) {
            DB::disconnect('pgsql');
            DB::purge('pgsql');
        }

        parent::tearDown();
    }

    private function postgresEnabled(): bool
    {
        return (string) getenv('NEWSROOM_POSTGRES_TESTS') === '1';
    }

    private function postgresEnv(string $key, string $default): string
    {
        $value = getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function setEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
