<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use Tests\Support\TestDatabaseConnection;

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
        TestDatabaseConnection::configure();
        TestDatabaseConnection::verifyConnectedIdentity();

        return parent::setUpTraits();
    }

    /**
     * Refuse to run against the configured main database or a non-local engine.
     */
    protected function ensureTestDatabaseIsDedicated(): void
    {
        TestDatabaseConnection::assertSafe();
    }
}
