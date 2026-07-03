<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_reachable(): void
    {
        $this->get('/app/login')->assertOk();
    }

    public function test_registration_page_is_reachable(): void
    {
        $this->get('/app/register')->assertOk();
    }

    public function test_guest_is_redirected_from_the_panel_to_login(): void
    {
        $this->get('/app')->assertRedirect('/app/login');
    }

    public function test_authenticated_user_can_open_the_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/app')->assertOk();
    }
}
