<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Days of click history shown on the stats page.
     */
    public const DAYS_WINDOW = 14;

    public function show(string $short_code): View
    {
        $link = Link::where('short_code', $short_code)->firstOrFail();

        $now = CarbonImmutable::now();

        $series = $this->buildDailySeries($link->id, self::DAYS_WINDOW, $now);
        $peak   = max(array_column($series, 'count')) ?: 0;
        $window = array_sum(array_column($series, 'count'));

        $last24h = (int) $link->clickLogs()
            ->where('clicked_at', '>=', $now->subDay())
            ->count();

        $last7d = (int) $link->clickLogs()
            ->where('clicked_at', '>=', $now->subDays(7))
            ->count();

        $prev7d = (int) $link->clickLogs()
            ->whereBetween('clicked_at', [
                $now->subDays(14), $now->subDays(7),
            ])
            ->count();

        $weekDelta = $this->percentChange($last7d, $prev7d);
        $avgPerDay = (int) round($window / self::DAYS_WINDOW);

        $topReferrers = $this->buildTopReferrers($link->id, self::DAYS_WINDOW, $now);

        $recent = $link->clickLogs()
            ->orderByDesc('clicked_at')
            ->limit(20)
            ->get();

        return view('links.stats', [
            'link'         => $link,
            'series'       => $series,
            'peak'         => $peak,
            'window'       => $window,
            'last24h'      => $last24h,
            'last7d'       => $last7d,
            'weekDelta'    => $weekDelta,
            'avgPerDay'    => $avgPerDay,
            'topReferrers' => $topReferrers,
            'recent'       => $recent,
        ]);
    }

    /**
     * @return array<int, array{date: \Carbon\CarbonImmutable, count: int, pct: int, isPeak: bool}>
     */
    private function buildDailySeries(int $linkId, int $days, CarbonImmutable $now): array
    {
        $today = $now->startOfDay();
        $start = $today->subDays($days - 1);

        $rows = DB::table('click_logs')
            ->selectRaw("date(clicked_at) as day, count(*) as total")
            ->where('link_id', $linkId)
            ->where('clicked_at', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day');

        $counts = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $key  = $date->format('Y-m-d');
            $counts[] = [
                'date'  => $date,
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }

        $peak = max(array_column($counts, 'count')) ?: 0;

        return array_map(static function (array $row) use ($peak): array {
            $row['pct']    = $peak > 0 ? (int) round(($row['count'] / $peak) * 100) : 0;
            $row['isPeak'] = $peak > 0 && $row['count'] === $peak;

            return $row;
        }, $counts);
    }

    /**
     * @return array<int, array{host: string, count: int, pct: int}>
     */
    private function buildTopReferrers(int $linkId, int $days, CarbonImmutable $now): array
    {
        $start = $now->subDays($days);

        $rows = DB::table('click_logs')
            ->select('referer')
            ->where('link_id', $linkId)
            ->where('clicked_at', '>=', $start)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $totals = [];
        foreach ($rows as $row) {
            $host = $this->refererHost($row->referer);
            $totals[$host] = ($totals[$host] ?? 0) + 1;
        }

        arsort($totals, SORT_NUMERIC);
        $totals = array_slice($totals, 0, 10, true);

        $total = array_sum($totals);

        $out = [];
        foreach ($totals as $host => $count) {
            $out[] = [
                'host'  => $host,
                'count' => (int) $count,
                'pct'   => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $out;
    }

    private function refererHost(?string $referer): string
    {
        if ($referer === null || $referer === '') {
            return 'Direct';
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return 'Direct';
        }

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    private function percentChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
