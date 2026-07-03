<?php

namespace Tests\Unit;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClickTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicks_do_not_use_updated_at_timestamps(): void
    {
        $click = new Click();

        $this->assertFalse($click->usesTimestamps());
    }

    public function test_created_at_is_cast_to_carbon(): void
    {
        $click = Click::factory()->create();

        $this->assertInstanceOf(Carbon::class, $click->created_at);
    }

    public function test_click_belongs_to_a_link(): void
    {
        $link = Link::factory()->create();
        $click = Click::factory()->create(['link_id' => $link->id]);

        $this->assertInstanceOf(Link::class, $click->link);
        $this->assertSame($link->id, $click->link->id);
    }
}
