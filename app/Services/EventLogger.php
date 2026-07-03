<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for domain/business event logging. Every event goes to the
 * dedicated "events" channel and is rendered by JsonEventFormatter into a
 * unified JSON schema (event, service, level, message, user_id, context).
 */
class EventLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $event, string $message, array $context = [], string $level = 'info'): void
    {
        Log::channel('events')->log($level, $message, array_merge([
            'event' => $event,
            'user_id' => Auth::id(),
        ], $context));
    }
}
