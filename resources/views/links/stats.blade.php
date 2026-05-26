@extends('layouts.app')

@section('title', "Analytics — trim/{$link->short_code}")

@php
    $shortUrl = url('/' . $link->short_code);
    $host = parse_url($shortUrl, PHP_URL_HOST) ?? request()->getHost();
    $totalClicks = (int) $link->click_count;
    $first = $series[0]['date'] ?? null;
    $last = $series[count($series) - 1]['date'] ?? null;

    $deltaIcon = $weekDelta > 0 ? 'up' : ($weekDelta < 0 ? 'down' : 'flat');
    $deltaSign = $weekDelta > 0 ? '+' : ($weekDelta < 0 ? '' : '±');
@endphp

@section('content')
    <div class="page-head">
        <div class="page-head__eyebrow">Analytics</div>
        <h1 class="page-head__title">Click analytics</h1>
        <p class="page-head__subtitle">
            Every visit to your short link is logged with timestamp, IP,
            referer, and user agent. Data shown is for the last
            {{ \App\Http\Controllers\StatsController::DAYS_WINDOW }} days.
        </p>
    </div>

    <section class="link-header" aria-label="Link summary">
        <div class="link-header__info">
            <div class="link-header__short">
                <span class="domain">{{ $host }}</span><span class="slash">/</span><span class="code">{{ $link->short_code }}</span>
            </div>
            <div class="link-header__original" title="{{ $link->original_url }}">
                <span class="arrow">→</span>
                <span>{{ $link->original_url }}</span>
            </div>
        </div>
        <div class="link-header__actions">
            <button class="btn btn--secondary btn--sm" type="button" id="copy-btn" data-url="{{ $shortUrl }}">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                <span id="copy-btn-label">Copy link</span>
            </button>
            <a class="btn btn--secondary btn--sm" href="{{ $shortUrl }}" target="_blank" rel="noopener">
                Open
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
            </a>
        </div>
    </section>

    <section class="kpi-grid" aria-label="Headline metrics">
        <div class="kpi">
            <div class="kpi__label-row">
                <span>Total clicks</span>
            </div>
            <div class="kpi__value tnum">{{ number_format($totalClicks) }}</div>
            <div class="muted" style="font-size: var(--t-sm);">
                {{ $totalClicks === 0 ? 'No visits yet' : 'Since ' . $link->created_at->format('M j, Y') }}
            </div>
        </div>
        <div class="kpi">
            <div class="kpi__label-row">
                <span>Last 24 hours</span>
            </div>
            <div class="kpi__value tnum">{{ number_format($last24h) }}</div>
            <div class="muted" style="font-size: var(--t-sm);">Visits in the last day</div>
        </div>
        <div class="kpi">
            <div class="kpi__label-row">
                <span>Last 7 days</span>
                <span class="kpi__delta kpi__delta--{{ $deltaIcon }}">
                    {{ $deltaSign }}{{ number_format(abs($weekDelta), 1) }}%
                </span>
            </div>
            <div class="kpi__value tnum">{{ number_format($last7d) }}</div>
            <div class="muted" style="font-size: var(--t-sm);">vs. prior 7 days</div>
        </div>
        <div class="kpi">
            <div class="kpi__label-row">
                <span>Avg / day</span>
            </div>
            <div class="kpi__value tnum">{{ number_format($avgPerDay) }}</div>
            <div class="muted" style="font-size: var(--t-sm);">
                Over {{ \App\Http\Controllers\StatsController::DAYS_WINDOW }}-day window
            </div>
        </div>
    </section>

    <section class="chart" aria-label="Clicks per day">
        <header class="chart__head">
            <div>
                <h2 class="chart__title" style="display: inline;">Clicks per day</h2>
                @if($first && $last)
                    <span class="chart__sub">
                        {{ $first->format('M j') }} – {{ $last->format('M j, Y') }}
                    </span>
                @endif
            </div>
            <div class="chart__legend">
                <span class="chart__legend-item"><span class="chart__legend-swatch"></span> Daily clicks</span>
                <span class="chart__legend-item"><span class="chart__legend-swatch chart__legend-swatch--peak"></span> Peak ({{ number_format($peak) }})</span>
                <span class="chart__legend-item"><span class="chart__legend-swatch chart__legend-swatch--empty"></span> No clicks</span>
            </div>
        </header>
        <div class="chart__body">
            @forelse($series as $row)
                @php
                    $isZero = $row['count'] === 0;
                    $isPeak = $row['isPeak'] && ! $isZero;
                @endphp
                <div class="bar-row @if($isPeak) bar-row--peak @endif">
                    <span class="bar-row__label">
                        <span class="dow">{{ $row['date']->format('D') }}</span>
                        <span class="date">{{ $row['date']->format('M j') }}</span>
                    </span>
                    <div class="bar-row__track">
                        <div class="bar-row__fill @if($isZero) bar-row__fill--zero @elseif($isPeak) bar-row__fill--peak @endif"
                             @if(! $isZero) style="width: {{ $row['pct'] }}%" @endif>
                        </div>
                    </div>
                    <span class="bar-row__value tnum">{{ number_format($row['count']) }}</span>
                </div>
            @empty
                <p class="muted">No clicks recorded yet.</p>
            @endforelse
        </div>
    </section>

    <section class="grid-2" aria-label="Referrers and recent clicks">
        <div class="card">
            <header class="card__header">
                <div>
                    <h2 class="card__title">Top referrers</h2>
                    <p class="card__subtitle">{{ \App\Http\Controllers\StatsController::DAYS_WINDOW }}-day totals</p>
                </div>
                <span class="badge">{{ count($topReferrers) }} {{ Str::plural('source', count($topReferrers)) }}</span>
            </header>
            <div class="card__body" style="padding-top: var(--s-4); padding-bottom: var(--s-4);">
                @if(empty($topReferrers))
                    <p class="muted" style="font-size: var(--t-md); margin: 0;">No referrer data yet.</p>
                @else
                    @php $maxRefPct = $topReferrers[0]['pct'] ?: 1; @endphp
                    <div class="ref-list">
                        @foreach($topReferrers as $ref)
                            <div class="ref-list__row">
                                <span class="ref-list__name">
                                    <span class="ref-list__favicon">
                                        {{ $ref['host'] === 'Direct' ? '·' : strtoupper(substr($ref['host'], 0, 1)) }}
                                    </span>
                                    @if($ref['host'] === 'Direct')
                                        <em>Direct</em>
                                    @else
                                        {{ $ref['host'] }}
                                    @endif
                                </span>
                                <span class="ref-list__value tnum">
                                    {{ number_format($ref['count']) }} · {{ $ref['pct'] }}%
                                </span>
                                <span class="ref-list__bar" style="width: {{ (int) round(($ref['pct'] / $maxRefPct) * 100) }}%;"></span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <header class="card__header">
                <div>
                    <h2 class="card__title">Recent clicks</h2>
                    <p class="card__subtitle">
                        Last {{ $recent->count() }} of {{ number_format($totalClicks) }} {{ Str::plural('entry', $totalClicks) }}
                    </p>
                </div>
            </header>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-time">Timestamp</th>
                            <th class="col-ip">IP address</th>
                            <th class="col-referer">Referer</th>
                            <th class="col-agent">Browser</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $click)
                            @php
                                $refererHost = null;
                                if ($click->referer) {
                                    $h = parse_url($click->referer, PHP_URL_HOST);
                                    $refererHost = is_string($h) ? (str_starts_with($h, 'www.') ? substr($h, 4) : $h) : null;
                                }
                                $agent = \App\Support\UserAgentParser::parse($click->user_agent);
                            @endphp
                            <tr>
                                <td class="col-time">
                                    <span class="date">{{ $click->clicked_at->format('M j') }}</span>
                                    <span class="clock">{{ $click->clicked_at->format('H:i:s') }}</span>
                                </td>
                                <td class="col-ip">{{ $click->ip_address }}</td>
                                <td class="col-referer">
                                    @if($refererHost)
                                        <span class="favicon">{{ strtoupper(substr($refererHost, 0, 1)) }}</span>{{ $refererHost }}
                                    @elseif($agent['is_bot'])
                                        <span class="badge badge--bot">Bot · {{ $agent['browser'] }}</span>
                                    @else
                                        <span class="badge badge--direct">Direct</span>
                                    @endif
                                </td>
                                <td class="col-agent" title="{{ $click->user_agent }}">
                                    @if($click->user_agent)
                                        <span class="browser">{{ $agent['label'] }}</span>
                                        @if($agent['os'] !== 'Unknown')
                                            <span class="os">{{ $agent['os_label'] }}</span>
                                        @endif
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: var(--s-7); color: var(--fg-muted);">
                                    No clicks recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div style="margin-top: var(--s-8); display: flex; justify-content: space-between; align-items: center; gap: var(--s-4);">
        <a class="btn btn--ghost" href="{{ route('links.create') }}">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 18l-6-6 6-6"/>
            </svg>
            Create another link
        </a>
        <span class="muted" style="font-size: var(--t-sm);">
            Showing data up to {{ now()->format('M j, Y · H:i') }} UTC
        </span>
    </div>

    @push('scripts')
        <script>
            (function () {
                const btn = document.getElementById('copy-btn');
                const label = document.getElementById('copy-btn-label');
                if (!btn || !label) return;
                btn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(btn.dataset.url);
                        label.textContent = 'Copied!';
                        setTimeout(() => { label.textContent = 'Copy link'; }, 1600);
                    } catch (e) {
                        label.textContent = 'Press Ctrl+C';
                        setTimeout(() => { label.textContent = 'Copy link'; }, 1600);
                    }
                });
            })();
        </script>
    @endpush
@endsection
