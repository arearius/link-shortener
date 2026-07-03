<?php

namespace Tests\Unit;

use App\Logging\JsonEventFormatter;
use Monolog\DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class JsonEventFormatterTest extends TestCase
{
    private function format(array $context): array
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable(true),
            channel: 'events',
            level: Level::Info,
            message: 'Short link created',
            context: $context,
            extra: [],
        );

        $output = (new JsonEventFormatter())->format($record);

        // A single line (newline only at the very end).
        $this->assertSame(1, substr_count($output, "\n"));

        return json_decode(trim($output), true);
    }

    public function test_it_promotes_event_and_user_id_to_the_top_level(): void
    {
        $data = $this->format([
            'event' => 'link.created',
            'user_id' => 5,
            'code' => 'abc123',
        ]);

        $this->assertSame('link.created', $data['event']);
        $this->assertSame('link-shortener', $data['service']);
        $this->assertSame(5, $data['user_id']);
        $this->assertSame('info', $data['level']);
        $this->assertSame('Short link created', $data['message']);
        $this->assertArrayHasKey('timestamp', $data);

        // Remaining keys stay under "context"; promoted keys are removed from it.
        $this->assertSame(['code' => 'abc123'], $data['context']);
    }

    public function test_it_handles_missing_event_and_empty_context(): void
    {
        $data = $this->format([]);

        $this->assertNull($data['event']);
        $this->assertNull($data['user_id']);
        $this->assertSame([], $data['context']);
    }
}
