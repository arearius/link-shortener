<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

/**
 * Emits every domain event as a single-line JSON record with a unified schema,
 * so all app events can be queried the same way in Grafana/Loki, e.g.
 *   {container_name="/link_shortener_app"} | json | event="link.created"
 *
 * Shape:
 *   {"timestamp":"…","level":"info","service":"link-shortener",
 *    "event":"link.created","message":"…","user_id":5,"context":{…}}
 */
class JsonEventFormatter extends JsonFormatter
{
    private const SERVICE = 'link-shortener';

    public function format(LogRecord $record): string
    {
        $context = $record->context;

        // Promote well-known keys to the top level; keep the rest under "context".
        $event = $context['event'] ?? null;
        $userId = $context['user_id'] ?? null;
        unset($context['event'], $context['user_id']);

        $data = [
            'timestamp' => $record->datetime->format(\DateTimeInterface::RFC3339_EXTENDED),
            'level' => $record->level->toPsrLogLevel(),
            'service' => self::SERVICE,
            'event' => $event,
            'message' => $record->message,
            'user_id' => $userId,
            'context' => $context === [] ? new \stdClass() : $context,
        ];

        return $this->toJson($data, true)."\n";
    }
}
