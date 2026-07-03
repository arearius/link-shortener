<?php

namespace Tests\Unit;

use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_code_has_the_configured_length(): void
    {
        $generator = new ShortCodeGenerator(length: 8);

        $this->assertSame(8, strlen($generator->generate()));
    }

    public function test_default_code_length_is_six(): void
    {
        $generator = new ShortCodeGenerator();

        $this->assertSame(6, strlen($generator->generate()));
    }

    public function test_generated_code_only_uses_the_base62_alphabet(): void
    {
        $generator = new ShortCodeGenerator();

        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $generator->generate());
        }
    }

    public function test_generate_unique_returns_a_code_not_present_in_the_database(): void
    {
        $existing = Link::factory()->create(['code' => 'abcdef']);

        $generator = new ShortCodeGenerator();
        $code = $generator->generateUnique();

        $this->assertNotSame($existing->code, $code);
        $this->assertDatabaseMissing('links', ['code' => $code]);
    }

    public function test_generate_unique_returns_a_valid_code(): void
    {
        $generator = new ShortCodeGenerator();
        $code = $generator->generateUnique();

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $code);
    }
}
