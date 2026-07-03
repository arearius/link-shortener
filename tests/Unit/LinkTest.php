<?php

namespace Tests\Unit;

use App\Models\Click;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_url_accessor_builds_url_from_code(): void
    {
        $link = Link::factory()->create(['code' => 'abc123']);

        $this->assertSame(url('abc123'), $link->short_url);
    }

    public function test_register_click_stores_a_click_and_increments_counter(): void
    {
        $link = Link::factory()->create(['clicks_count' => 0]);

        $request = Request::create('/'.$link->code, 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);

        $click = $link->registerClick($request);

        $this->assertDatabaseHas('clicks', [
            'id' => $click->id,
            'link_id' => $link->id,
            'ip_address' => '203.0.113.7',
        ]);
        $this->assertSame(1, $link->fresh()->clicks_count);
    }

    public function test_register_click_stores_user_agent_and_timestamp(): void
    {
        $link = Link::factory()->create();

        $request = Request::create('/'.$link->code, 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_USER_AGENT' => 'PHPUnit-Agent',
        ]);

        $click = $link->registerClick($request);

        $this->assertSame('PHPUnit-Agent', $click->user_agent);
        $this->assertInstanceOf(Carbon::class, $click->created_at);
    }

    public function test_it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $link->user);
        $this->assertSame($user->id, $link->user->id);
    }

    public function test_it_has_many_clicks(): void
    {
        $link = Link::factory()->create();
        Click::factory()->count(3)->create(['link_id' => $link->id]);

        $this->assertCount(3, $link->clicks);
        $this->assertInstanceOf(Click::class, $link->clicks->first());
    }

    public function test_clicks_count_is_cast_to_integer(): void
    {
        $link = Link::factory()->create(['clicks_count' => '5']);

        $this->assertSame(5, $link->fresh()->clicks_count);
    }
}
