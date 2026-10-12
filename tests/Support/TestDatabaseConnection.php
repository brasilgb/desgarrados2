<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Configures and validates the separately provisioned local integration-test database. */
final class TestDatabaseConnection
{
    public static function configure(): void
    {
        $driver = (string) env('TEST_DB_CONNECTION', '');
        $database = (string) env('TEST_DB_DATABASE', '');
        $mainName = (string) config('database.connections.'.config('database.default').'.database', '');

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Set TEST_DB_CONNECTION to mysql or mariadb before running integration tests.');
        }
        if ($database === '' || $database === ':memory:' || $database === $mainName) {
            throw new RuntimeException('TEST_DB_DATABASE must name a database separate from the configured main database.');
        }

        $host = (string) env('TEST_DB_HOST', '127.0.0.1');
        if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('Integration tests are restricted to a local MySQL or MariaDB host.');
        }

        config([
            'database.connections.testing' => [
                'driver' => $driver,
                'host' => $host,
                'port' => (string) env('TEST_DB_PORT', '3306'),
                'database' => $database,
                'username' => (string) env('TEST_DB_USERNAME', env('DB_USERNAME', '')),
                'password' => (string) env('TEST_DB_PASSWORD', env('DB_PASSWORD', '')),
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => [],
            ],
            'database.default' => 'testing',
        ]);

        DB::purge('testing');
    }

    public static function assertSafe(): void
    {
        $connection = config('database.connections.testing');
        $database = (string) ($connection['database'] ?? '');
        $main = config('database.connections.'.env('DB_CONNECTION', config('database.default')));
        $mainName = (string) ($main['database'] ?? '');

        if (! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)
            || $database === '' || $database === ':memory:' || $database === $mainName
            || ! in_array($connection['host'] ?? null, ['127.0.0.1', 'localhost', '::1'], true)
            || ! empty($connection['url']) || ! empty($connection['unix_socket'])
            || ! empty($connection['read']) || ! empty($connection['write'])) {
            throw new RuntimeException('Refusing tests: configure a local MySQL/MariaDB database distinct from the main database using TEST_DB_* variables.');
        }
    }

    public static function verifyConnectedIdentity(): void
    {
        self::assertSafe();
        $connection = DB::connection('testing');
        $actualDriver = $connection->getDriverName();
        $actualDatabase = $connection->getDatabaseName();
        $configured = config('database.connections.testing');

        if (! in_array($actualDriver, ['mysql', 'mariadb'], true)
            || $actualDatabase !== $configured['database']) {
            throw new RuntimeException('The connected test database identity does not match TEST_DB_* configuration.');
        }
    }
}
