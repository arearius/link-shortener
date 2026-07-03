<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures a short link points to an external http/https address and cannot be
 * used to redirect visitors to the application's own host or to loopback/internal
 * addresses (which would let a public short link reach service paths like /app).
 */
class ExternalUrl implements ValidationRule
{
    /**
     * Hosts that must never be a redirect target.
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Укажите корректный URL.');

            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));

        if (! in_array($scheme, ['http', 'https'], true)) {
            $fail('URL должен начинаться с http:// или https://.');

            return;
        }

        if ($host === '') {
            $fail('URL должен содержать домен.');

            return;
        }

        if (in_array($host, self::BLOCKED_HOSTS, true) || $host === $this->appHost()) {
            $fail('Нельзя создавать ссылку на внутренний адрес приложения.');

            return;
        }
    }

    /**
     * Host of the application itself (from APP_URL), if any.
     */
    private function appHost(): ?string
    {
        $appUrl = (string) config('app.url');

        $host = parse_url($appUrl, PHP_URL_HOST);

        return $host ? strtolower($host) : null;
    }
}
