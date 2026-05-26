<?php

namespace Tests\Feature;

use App\Models\ClickLog;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectAndClickCountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_code_issues_a_302_redirect_to_the_original_url(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://laravel.com/docs/10.x',
            'short_code'   => 'aB3xQ9',
        ]);

        $response = $this->get('/aB3xQ9');

        $response->assertStatus(302);
        $response->assertRedirect('https://laravel.com/docs/10.x');
    }

    public function test_click_is_logged_after_the_response_with_request_metadata(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://example.com',
            'short_code'   => 'aB3xQ9',
            'click_count'  => 0,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.41'])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 TestRunner',
                'Referer'    => 'https://news.ycombinator.com/',
            ])
            ->get('/aB3xQ9')
            ->assertRedirect('https://example.com');

        $this->assertDatabaseCount('click_logs', 1);

        $log = ClickLog::firstOrFail();

        $this->assertSame($link->id, $log->link_id);
        $this->assertSame('203.0.113.41', $log->ip_address);
        $this->assertSame('Mozilla/5.0 TestRunner', $log->user_agent);
        $this->assertSame('https://news.ycombinator.com/', $log->referer);
        $this->assertNotNull($log->clicked_at);

        $this->assertSame(1, $link->fresh()->click_count);
    }

    public function test_multiple_visits_increment_click_count_and_create_multiple_logs(): void
    {
        $link = Link::factory()->create([
            'short_code'  => 'mLT123',
            'click_count' => 0,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->get('/mLT123')->assertStatus(302);
        }

        $this->assertSame(3, $link->fresh()->click_count);
        $this->assertSame(3, ClickLog::where('link_id', $link->id)->count());
    }

    public function test_an_unknown_short_code_returns_a_404_view(): void
    {
        $response = $this->get('/abc123');

        $response->assertStatus(404);
        $response->assertSee('Link not found');
    }
}
