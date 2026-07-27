<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function usesIsolatedSqliteDatabase(): bool
    {
        try {
            return DB::connection()->getDriverName() === 'sqlite'
                && DB::connection()->getDatabaseName() === ':memory:';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function requireIsolatedSqliteDatabase(): void
    {
        if (! $this->usesIsolatedSqliteDatabase()) {
            throw new \RuntimeException(static::class.' chỉ được phép chạy trên SQLite :memory: cô lập.');
        }
    }
}
