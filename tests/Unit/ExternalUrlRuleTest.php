<?php

namespace Tests\Unit;

use App\Rules\ExternalUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExternalUrlRuleTest extends TestCase
{
    private function passes(string $url): bool
    {
        return Validator::make(
            ['url' => $url],
            ['url' => new ExternalUrl()],
        )->passes();
    }

    public static function validUrls(): array
    {
        return [
            ['https://example.com/page'],
            ['http://sub.example.org/a/b?c=1'],
        ];
    }

    public static function invalidUrls(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'ftp scheme' => ['ftp://example.com/file'],
            'no host' => ['https://'],
            'plain text' => ['not a url'],
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

    public function test_it_rejects_the_application_host(): void
    {
        config(['app.url' => 'https://links.example.test']);

        $this->assertFalse($this->passes('https://links.example.test/app/login'));
    }
}
