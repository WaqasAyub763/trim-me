<?php

namespace App\Services;

use App\Models\Link;
use RuntimeException;

/**
 * Generates collision-resistant short codes for {@see Link}.
 *
 * The generator draws random bytes, maps them onto a base62 alphabet,
 * then verifies uniqueness against the {@see Link::$short_code} column
 * before returning. {@see self::generate()} is the only consumer-facing
 * method; {@see self::randomCode()} is exposed so tests can mock it
 * and seed deterministic candidates.
 */
class ShortCodeGenerator
{
    /**
     * Base62 alphabet: 0-9, a-z, A-Z. 62^6 ≈ 56.8 billion combinations.
     */
    public const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Default length of a generated short code.
     */
    public const LENGTH = 6;

    /**
     * Maximum attempts before giving up. With 56.8B combinations and a
     * mostly-empty table, a collision is astronomically unlikely; this
     * is a safety net rather than an expected branch.
     */
    public const MAX_ATTEMPTS = 8;

    /**
     * Generate a unique short code, guaranteed not to collide with any
     * existing {@see Link::$short_code} value at the moment of generation.
     *
     * @throws RuntimeException when the generator exhausts {@see self::MAX_ATTEMPTS}.
     */
    public function generate(): string
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $this->randomCode(self::LENGTH);

            if (! Link::where('short_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf(
            'Unable to generate a unique short code after %d attempts.',
            self::MAX_ATTEMPTS
        ));
    }

    /**
     * Pure random code, no DB check. Mockable in tests to seed candidates.
     */
    public function randomCode(int $length): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }
}
