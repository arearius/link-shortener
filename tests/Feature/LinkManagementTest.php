<?php

namespace Tests\Feature;

use App\Filament\Resources\LinkResource;
use App\Filament\Resources\LinkResource\Pages\CreateLink;
use App\Filament\Resources\LinkResource\Pages\ListLinks;
use App\Models\Link;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LinkManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_user_sees_only_their_own_links(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $own = Link::factory()->create(['user_id' => $user->id]);
        $foreign = Link::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user);

        Livewire::test(ListLinks::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_creating_a_link_generates_a_code_and_assigns_the_owner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateLink::class)
            ->fillForm(['original_url' => 'https://example.com/page'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://example.com/page',
        ]);

        $link = Link::where('user_id', $user->id)->first();
        $this->assertNotEmpty($link->code);
    }

    public function test_user_can_delete_their_own_link(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(ListLinks::class)
            ->callTableAction('delete', $link);

        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_user_cannot_access_another_users_link(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Link::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user);

        $this->get(LinkResource::getUrl('edit', ['record' => $foreign]))
            ->assertNotFound();
    }
}
