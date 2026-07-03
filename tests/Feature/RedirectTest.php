<?php

namespace Tests\Feature;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_code_redirects_to_original_url_and_records_click(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://example.com/page',
            'code' => 'abc123',
            'clicks_count' => 0,
        ]);

        $response = $this->get('/abc123');

        $response->assertRedirect('https://example.com/page');

        $this->assertDatabaseCount('clicks', 1);
        $this->assertDatabaseHas('clicks', ['link_id' => $link->id]);
        $this->assertSame(1, $link->fresh()->clicks_count);
    }

    public function test_click_records_the_visitor_ip(): void
    {
        Link::factory()->create(['code' => 'ipcode']);

        $this->get('/ipcode', ['REMOTE_ADDR' => '198.51.100.10']);

        $this->assertDatabaseHas('clicks', ['ip_address' => '198.51.100.10']);
    }

    public function test_unknown_code_returns_404(): void
    {
        $response = $this->get('/missing');

        $response->assertNotFound();
        $this->assertDatabaseCount('clicks', 0);
    }
}
