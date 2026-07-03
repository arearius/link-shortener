<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

class EventLoggingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Capture records written to the "events" channel.
     */
    private function tapEvents(): TestHandler
    {
        $handler = new TestHandler();
        Log::channel('events')->getLogger()->pushHandler($handler);

        return $handler;
    }

    private function loggedEvent(TestHandler $handler, string $event): bool
    {
        foreach ($handler->getRecords() as $record) {
            if (($record->context['event'] ?? null) === $event) {
                return true;
            }
        }

        return false;
    }

    public function test_creating_a_link_logs_link_created(): void
    {
        $handler = $this->tapEvents();

        Link::factory()->create();

        $this->assertTrue($this->loggedEvent($handler, 'link.created'));
    }

    public function test_deleting_a_link_logs_link_deleted(): void
    {
        $link = Link::factory()->create();
        $handler = $this->tapEvents();

        $link->delete();

        $this->assertTrue($this->loggedEvent($handler, 'link.deleted'));
    }

    public function test_visiting_a_short_link_logs_link_clicked(): void
    {
        Link::factory()->create(['code' => 'abc123']);
        $handler = $this->tapEvents();

        $this->get('/abc123');

        $this->assertTrue($this->loggedEvent($handler, 'link.clicked'));
    }

    public function test_login_event_logs_auth_login(): void
    {
        $user = User::factory()->create();
        $handler = $this->tapEvents();

        event(new Login('web', $user, false));

        $this->assertTrue($this->loggedEvent($handler, 'auth.login'));
    }
}
