<?php

namespace Tests\Unit;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
}
