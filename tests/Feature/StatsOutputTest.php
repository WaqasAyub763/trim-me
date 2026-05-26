<?php

namespace Tests\Feature;

use App\Http\Controllers\StatsController;
use App\Models\ClickLog;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsOutputTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_page_renders_for_a_known_short_code(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://laravel.com/docs',
            'short_code'   => 'sTaTs1',
            'click_count'  => 0,
        ]);

        $response = $this->get(route('links.stats', $link->short_code));

        $response->assertOk();
        $response->assertSee('sTaTs1');
        $response->assertSee('https://laravel.com/docs');
        $response->assertSee('Click analytics');
    }

    public function test_stats_page_shows_correct_total_click_count(): void
    {
        $link = Link::factory()->create([
            'short_code'  => 'CNTRR1',
            'click_count' => 42,
        ]);

        $response = $this->get(route('links.stats', $link->short_code));

        $response->assertOk();
        $response->assertSee('Total clicks');
        $response->assertSee('42');
    }

    public function test_stats_page_buckets_clicks_into_the_correct_days(): void
    {
        $link = Link::factory()->create(['short_code' => 'BUCKT1']);

        ClickLog::factory()->forLink($link)->count(3)
            ->within('-1 days', 'now')->create();
        ClickLog::factory()->forLink($link)->count(5)
            ->within('-3 days 12:00', '-3 days 23:59')->create();
        ClickLog::factory()->forLink($link)->count(2)
            ->within('-7 days', '-7 days +6 hours')->create();

        $response = $this->get(route('links.stats', $link->short_code));

        $response->assertOk();

        $series = $response->viewData('series');
        $this->assertIsArray($series);
        $this->assertCount(StatsController::DAYS_WINDOW, $series);
        $this->assertSame(10, array_sum(array_column($series, 'count')));

        // Newest bucket is last; verify shape.
        $latest = end($series);
        $this->assertArrayHasKey('date', $latest);
        $this->assertArrayHasKey('count', $latest);
        $this->assertArrayHasKey('pct', $latest);
        $this->assertGreaterThanOrEqual(0, $latest['pct']);
        $this->assertLessThanOrEqual(100, $latest['pct']);
    }

    public function test_recent_clicks_table_is_capped_at_twenty_entries(): void
    {
        $link = Link::factory()->create(['short_code' => 'RCNT01']);

        ClickLog::factory()->forLink($link)->count(35)->create();

        $response = $this->get(route('links.stats', $link->short_code));

        $response->assertOk();

        $recent = $response->viewData('recent');
        $this->assertCount(20, $recent);
    }

    public function test_recent_clicks_are_ordered_newest_first(): void
    {
        $link = Link::factory()->create(['short_code' => 'ORDR01']);

        ClickLog::factory()->forLink($link)->create([
            'clicked_at' => now()->subDays(5),
        ]);
        ClickLog::factory()->forLink($link)->create([
            'clicked_at' => now()->subMinutes(2),
        ]);
        ClickLog::factory()->forLink($link)->create([
            'clicked_at' => now()->subHour(),
        ]);

        $response = $this->get(route('links.stats', $link->short_code));

        $recent = $response->viewData('recent');
        $this->assertGreaterThanOrEqual(
            $recent[1]->clicked_at->timestamp,
            $recent[0]->clicked_at->timestamp,
        );
        $this->assertGreaterThanOrEqual(
            $recent[2]->clicked_at->timestamp,
            $recent[1]->clicked_at->timestamp,
        );
    }

    public function test_stats_page_returns_404_for_unknown_short_code(): void
    {
        $response = $this->get(route('links.stats', 'zzzzzz'));

        $response->assertStatus(404);
    }
}
