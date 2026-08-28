<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Guard against tests running against a real/populated database.
     *
     * phpunit.xml sets DB_CONNECTION=sqlite / DB_DATABASE=:memory: via <php><env>,
     * but if bootstrap/cache/config.php was generated (php artisan config:cache)
     * before that env was active, Laravel loads the cached config verbatim and
     * env() calls resolve to whatever was baked in at cache time — silently
     * pointing the test suite at a real database. This check fails loudly,
     * before any test body (and therefore any RefreshDatabase/migration/seed
     * call) can run against the wrong connection.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->guardAgainstUnsafeTestDatabase();
    }

    private function guardAgainstUnsafeTestDatabase(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException(
                'Refusing to run tests: application environment is "'.app()->environment().'", not "testing". '.
                'This usually means a cached config (bootstrap/cache/config.php) was generated outside the '.
                'testing environment. Run `php artisan config:clear` and try again.'
            );
        }

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite' || $connection->getConfig('database') !== ':memory:') {
            throw new RuntimeException(
                'Refusing to run tests: the default database connection is not sqlite ":memory:" '.
                '(driver: '.$connection->getDriverName().', database: '.var_export($connection->getConfig('database'), true).'). '.
                'This can happen when bootstrap/cache/config.php was cached before APP_ENV=testing / DB_CONNECTION=sqlite '.
                'were active, so env() calls resolved to the wrong values at cache time. Run `php artisan config:clear` '.
                'before running the test suite.'
            );
        }
    }
}
