<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Guard the database before traits such as RefreshDatabase touch it.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits(): array
    {
        $this->ensureTestDatabaseIsDedicated();

        return parent::setUpTraits();
    }

    /**
     * Refuse to run against any database that is not dedicated to tests.
     */
    protected function ensureTestDatabaseIsDedicated(): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $database = (string) ($connection['database'] ?? '');

        if ($database !== 'desgarrados2_test') {
            throw new RuntimeException("Refusing to run tests against non-test database [{$database}].");
        }

        if (config('database.default') !== 'mariadb'
            || ($connection['driver'] ?? null) !== 'mariadb'
            || ($connection['host'] ?? null) !== '127.0.0.1'
            || (string) ($connection['port'] ?? '') !== '3306'
            || ($connection['username'] ?? null) !== 'desgarrados'
            || ! empty($connection['url'])
            || ! empty($connection['unix_socket'])
            || ! empty($connection['read'])
            || ! empty($connection['write'])) {
            throw new RuntimeException('Refusing to run tests with a connection outside the dedicated MariaDB configuration.');
        }
    }
}
