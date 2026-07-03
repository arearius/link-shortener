<?php

namespace App\Listeners;

use App\Services\EventLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;

/**
 * Logs authentication lifecycle events (fired by the framework and Filament)
 * as structured domain events.
 */
class LogAuthenticationEvents
{
    public function __construct(private readonly EventLogger $events)
    {
    }

    public function handleLogin(Login $event): void
    {
        $this->events->record('auth.login', 'User logged in', [
            'user_id' => $event->user->getAuthIdentifier(),
            'email' => $event->user->email ?? null,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $this->events->record('auth.logout', 'User logged out', [
            'user_id' => $event->user?->getAuthIdentifier(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        $this->events->record('auth.failed', 'Failed login attempt', [
            'email' => $event->credentials['email'] ?? null,
        ], 'warning');
    }

    public function handleRegistered(Registered $event): void
    {
        $this->events->record('auth.registered', 'User registered', [
            'user_id' => $event->user->getAuthIdentifier(),
            'email' => $event->user->email ?? null,
        ]);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Registered::class => 'handleRegistered',
        ];
    }
}
