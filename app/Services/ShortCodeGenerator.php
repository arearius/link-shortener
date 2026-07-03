<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Str;

class ShortCodeGenerator
{
    /**
     * Base62 alphabet (URL-safe, no ambiguous separators).
     */
    public const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(
        private readonly int $length = 6,
    ) {
    }

    /**
     * Generate a random base62 code of the configured length.
     */
    public function generate(): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;

        $code = '';
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Generate a code that is not yet present in the links table.
     * Grows the length if the space gets crowded to avoid an infinite loop.
     */
    public function generateUnique(): string
    {
        do {
            $code = $this->generate();
        } while (Link::where('code', $code)->exists());

        return $code;
    }
}
