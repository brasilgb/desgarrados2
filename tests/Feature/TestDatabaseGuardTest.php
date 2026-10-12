<?php

use Tests\Support\TestDatabaseConnection;

test('test database must be different from the configured main database', function () {
    $mainDatabase = config('database.connections.'.env('DB_CONNECTION', 'mysql').'.database');
    config(['database.connections.testing.database' => $mainDatabase]);

    TestDatabaseConnection::assertSafe();
})->throws(RuntimeException::class, 'configure a local MySQL/MariaDB database distinct from the main database');

test('local mysql and mariadb test connections with a separate database are allowed', function (string $driver) {
    config(['database.connections.testing.driver' => $driver, 'database.connections.testing.database' => 'test_fixture_isolation']);

    TestDatabaseConnection::assertSafe();
    expect(true)->toBeTrue();
})->with(['mysql', 'mariadb']);

test('unsafe test database endpoints are refused', function (string $key, mixed $value) {
    config(['database.connections.testing.'.$key => $value]);

    TestDatabaseConnection::assertSafe();
})->with([
    'remote host' => ['host', '192.0.2.1'],
    'unsupported driver' => ['driver', 'sqlite'],
    'URL override' => ['url', 'mysql://localhost/test_fixture_isolation'],
    'socket override' => ['unix_socket', '/tmp/mysql.sock'],
    'read override' => ['read', ['database' => 'primary']],
    'write override' => ['write', ['database' => 'primary']],
])->throws(RuntimeException::class, 'configure a local MySQL/MariaDB database distinct from the main database');
