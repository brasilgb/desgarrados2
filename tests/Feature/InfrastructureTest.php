<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;

test('sessions can be persisted and removed using MariaDB', function () {
    $handler = Session::driver('database')->getHandler();
    $sessionId = str_repeat('a', 40);

    expect($handler->write($sessionId, 'foundation-session'))->toBeTrue();
    expect($handler->read($sessionId))->toBe('foundation-session');
    $this->assertDatabaseHas('sessions', ['id' => $sessionId]);

    expect($handler->destroy($sessionId))->toBeTrue();
    $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
});

test('cache values and exclusive locks can be stored using MariaDB', function () {
    $cache = Cache::store('database');

    $cache->put('foundation-check', 'ready', 60);

    expect($cache->get('foundation-check'))->toBe('ready');
    expect($cache->forget('foundation-check'))->toBeTrue();
    expect($cache->get('foundation-check'))->toBeNull();

    $lock = $cache->lock('foundation-lock', 60);

    expect($lock->get())->toBeTrue();
    expect($cache->lock('foundation-lock', 60)->get())->toBeFalse();
    expect($lock->release())->toBeTrue();
});

test('queue payloads can be enqueued reserved and removed using MariaDB', function () {
    $queue = Queue::connection('database');
    $payload = '{"displayName":"foundation-check","job":"foundation-check","data":{}}';

    $jobId = $queue->pushRaw($payload, 'foundation-check');

    $this->assertDatabaseHas('jobs', ['id' => $jobId, 'queue' => 'foundation-check']);
    $job = $queue->pop('foundation-check');
    expect($job)->not->toBeNull();
    expect($job->getRawBody())->toBe($payload);

    $job->delete();

    $this->assertDatabaseMissing('jobs', ['id' => $jobId]);
});
