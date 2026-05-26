<?php

namespace Tests\Feature;

use App\Models\ClickLog;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiredLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_link_returns_a_410_expired_view(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://example.com/promo',
            'short_code'   => 'EXPRD1',
            'expires_at'   => now()->subDay(),
            'click_count'  => 0,
        ]);

        $response = $this->get('/EXPRD1');

        $response->assertStatus(410);
        $response->assertSee('This link has expired');
        $response->assertSee('EXPRD1');
    }

    public function test_expired_link_does_not_log_a_click_or_bump_the_counter(): void
    {
        $link = Link::factory()->create([
            'short_code'  => 'EXPRD2',
            'expires_at'  => now()->subHour(),
            'click_count' => 0,
        ]);

        $this->get('/EXPRD2');

        $this->assertSame(0, $link->fresh()->click_count);
        $this->assertSame(0, ClickLog::where('link_id', $link->id)->count());
    }

    public function test_a_link_with_future_expiry_still_redirects(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://example.com',
            'short_code'   => 'FUTUR1',
            'expires_at'   => now()->addDay(),
            'click_count'  => 0,
        ]);

        $response = $this->get('/FUTUR1');

        $response->assertStatus(302);
        $response->assertRedirect('https://example.com');
        $this->assertSame(1, $link->fresh()->click_count);
    }

    public function test_is_expired_accessor_returns_correct_value(): void
    {
        $past = Link::factory()->create(['expires_at' => now()->subSecond()]);
        $future = Link::factory()->create(['expires_at' => now()->addDay()]);
        $never = Link::factory()->create(['expires_at' => null]);

        $this->assertTrue($past->is_expired);
        $this->assertFalse($future->is_expired);
        $this->assertFalse($never->is_expired);
    }
}
