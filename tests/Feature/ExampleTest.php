<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root URL redirects to the Filament dashboard.
     */
    public function test_root_redirects_to_the_panel(): void
    {
        $this->get('/')->assertRedirect('/app');
    }
}
