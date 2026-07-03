<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_migrate_runs_successfully(): void
    {
        $this->artisan('app:migrate')->assertSuccessful();
    }
}
