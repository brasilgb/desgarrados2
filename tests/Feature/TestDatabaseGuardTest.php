<?php

test('running against a non-test database is refused', function (string $database) {
    config(['database.connections.'.config('database.default').'.database' => $database]);

    $this->ensureTestDatabaseIsDedicated();
})->with(['desgarrados2', 'another_project_test', ':memory:'])
    ->throws(RuntimeException::class, 'Refusing to run tests against non-test database');

test('running against a dedicated test database is allowed', function () {
    config(['database.connections.'.config('database.default').'.database' => 'desgarrados2_test']);

    $this->ensureTestDatabaseIsDedicated();

    expect(true)->toBeTrue();
});

test('running with an unsafe database connection is refused', function (string $key, mixed $value) {
    config(['database.connections.mariadb.'.$key => $value]);

    $this->ensureTestDatabaseIsDedicated();
})->with([
    'root user' => ['username', 'root'],
    'another host' => ['host', '192.0.2.1'],
    'another port' => ['port', '3307'],
    'another driver' => ['driver', 'sqlite'],
    'URL override' => ['url', 'mysql://127.0.0.1/desgarrados2'],
    'socket override' => ['unix_socket', '/tmp/mysql.sock'],
    'read override' => ['read', ['database' => 'desgarrados2']],
    'write override' => ['write', ['database' => 'desgarrados2']],
])->throws(RuntimeException::class, 'Refusing to run tests with a connection outside the dedicated MariaDB configuration.');
