<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The array cache store is recreated with the fresh app per test,
        // but flush defensively in case a prior test in this file left
        // throttle counters in the store.
        Cache::flush();
    }

    public function test_ten_links_per_hour_per_ip_are_allowed(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->post(route('links.store'), [
                'original_url' => "https://example.com/page-{$i}",
            ]);

            $response->assertStatus(302);
            $this->assertNotEquals(
                429,
                $response->status(),
                "Request #{$i} should not be throttled"
            );
        }

        $this->assertDatabaseCount('links', 10);
    }

    public function test_eleventh_link_in_the_same_hour_returns_429(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('links.store'), [
                'original_url' => "https://example.com/page-{$i}",
            ])->assertStatus(302);
        }

        $response = $this->post(route('links.store'), [
            'original_url' => 'https://example.com/over-limit',
        ]);

        $response->assertStatus(429);
        $this->assertDatabaseCount('links', 10);
    }

    public function test_rate_limit_is_scoped_per_ip(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('links.store'), [
                'original_url' => "https://example.com/page-{$i}",
            ])->assertStatus(302);
        }

        // A request from a different IP should not be throttled.
        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->post(route('links.store'), [
                'original_url' => 'https://example.com/other-ip',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseCount('links', 11);
    }
}
