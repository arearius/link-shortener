<?php

namespace Tests\Unit;

use App\Rules\ExternalUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExternalUrlRuleTest extends TestCase
{
    private function passes(mixed $value): bool
    {
        return Validator::make(
            ['url' => $value],
            ['url' => new ExternalUrl()],
        )->passes();
    }

    public static function validUrls(): array
    {
        return [
            'https' => ['https://example.com/page'],
            'http with query' => ['http://sub.example.org/a/b?c=1'],
            'uppercase scheme' => ['HTTPS://example.com'],
            'external host with port' => ['https://example.com:8443/x'],
        ];
    }

    public static function invalidUrls(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'ftp scheme' => ['ftp://example.com/file'],
            'no host' => ['https://'],
            'plain text' => ['not a url'],
            'protocol-relative (no scheme)' => ['//example.com/path'],
            'localhost' => ['http://localhost:8080/app'],
            'loopback ip' => ['http://127.0.0.1/app/login'],
        ];
    }

    /** @dataProvider validUrls */
    public function test_it_accepts_external_http_urls(string $url): void
    {
        $this->assertTrue($this->passes($url));
    }

    /** @dataProvider invalidUrls */
    public function test_it_rejects_non_external_or_invalid_urls(string $url): void
    {
        $this->assertFalse($this->passes($url));
    }

    public function test_it_rejects_the_application_host_case_insensitively(): void
    {
        config(['app.url' => 'https://links.example.test']);

        $this->assertFalse($this->passes('https://links.example.test/app/login'));
        $this->assertFalse($this->passes('HTTPS://LINKS.EXAMPLE.TEST/app'));
    }

    public function test_it_rejects_a_non_string_value(): void
    {
        $this->assertFalse($this->passes(['not', 'a', 'string']));
    }
}
