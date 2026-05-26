@extends('layouts.app')

@section('title', "Link created — trim/{$link->short_code}")
@section('page_class', 'page--narrow')

@php
    $shortUrl = url('/' . $link->short_code);
    $host = parse_url($shortUrl, PHP_URL_HOST) ?? request()->getHost();
@endphp

@section('content')
    <div class="page-head">
        <div class="page-head__eyebrow">{{ $justCreated ? 'Link created' : 'Link details' }}</div>
        <h1 class="page-head__title">
            {{ $justCreated ? 'Your short link is ready' : 'Short link details' }}
        </h1>
        <p class="page-head__subtitle">
            Copy the URL below to share it. Click data starts being recorded
            as soon as the first visit comes through.
        </p>
    </div>

    @if($justCreated)
        <div class="alert alert--success" style="margin-bottom: var(--s-6);">
            <svg class="alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <div class="alert__body">
                <div class="alert__title">Link created successfully</div>
                <div class="alert__text">
                    Saved to the database. Short code <span class="mono" style="color: var(--fg);">{{ $link->short_code }}</span> is now active.
                </div>
            </div>
        </div>
    @endif

    <section class="card" aria-labelledby="result-card-title">
        <header class="card__header">
            <div>
                <h2 class="card__title" id="result-card-title">Short link</h2>
                <p class="card__subtitle">Resolves with a 302 redirect on every visit.</p>
            </div>
            <a class="btn btn--secondary btn--sm" href="{{ route('links.stats', $link->short_code) }}">
                View analytics
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </a>
        </header>

        <div class="card__body">
            <div class="short-link">
                <span class="short-link__value" id="short-link-value" data-url="{{ $shortUrl }}">
                    <span class="protocol">{{ request()->isSecure() ? 'https://' : 'http://' }}</span><span class="domain">{{ $host }}</span><span class="slash">/</span><span class="code">{{ $link->short_code }}</span>
                </span>
                <div class="short-link__actions">
                    <button class="btn btn--secondary btn--sm" type="button" id="copy-btn">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        <span id="copy-btn-label">Copy</span>
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
            </div>

            <div class="original-url">
                <span class="original-url__label">Destination</span>
                <span class="original-url__value">{{ $link->original_url }}</span>
            </div>

            <div class="meta-grid">
                <div class="meta-grid__cell">
                    <div class="meta-grid__label">Created</div>
                    <div class="meta-grid__value">{{ $link->created_at->format('M j, Y') }}</div>
                    <div class="meta-grid__sub">
                        {{ $link->created_at->format('H:i') }} UTC · {{ $link->created_at->diffForHumans() }}
                    </div>
                </div>
                <div class="meta-grid__cell">
                    <div class="meta-grid__label">Expires</div>
                    @if($link->expires_at)
                        <div class="meta-grid__value">{{ $link->expires_at->format('M j, Y') }}</div>
                        <div class="meta-grid__sub">{{ $link->expires_at->diffForHumans() }}</div>
                    @else
                        <div class="meta-grid__value">Never</div>
                        <div class="meta-grid__sub">No expiry set</div>
                    @endif
                </div>
                <div class="meta-grid__cell">
                    <div class="meta-grid__label">Short code</div>
                    <div class="meta-grid__value meta-grid__value--mono">{{ $link->short_code }}</div>
                    <div class="meta-grid__sub">6 chars · base62</div>
                </div>
                <div class="meta-grid__cell">
                    <div class="meta-grid__label">Total clicks</div>
                    <div class="meta-grid__value tnum">{{ number_format($link->click_count) }}</div>
                    <div class="meta-grid__sub">
                        {{ $link->click_count === 0 ? 'Waiting for first visit' : 'Across all referrers' }}
                    </div>
                </div>
            </div>
        </div>

        <footer class="card__footer">
            <span class="muted" style="font-size: var(--t-md);">
                Bookmark the analytics page — it's the only way back to this link's history.
            </span>
            <div class="row">
                <a class="btn btn--ghost" href="{{ route('links.create') }}">Create another</a>
                <a class="btn btn--primary" href="{{ route('links.stats', $link->short_code) }}">
                    View analytics
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </div>
        </footer>
    </section>

    @push('scripts')
        <script>
            (function () {
                const btn = document.getElementById('copy-btn');
                const label = document.getElementById('copy-btn-label');
                const value = document.getElementById('short-link-value');
                if (!btn || !value || !label) return;
                btn.addEventListener('click', async () => {
                    const url = value.dataset.url;
                    try {
                        await navigator.clipboard.writeText(url);
                        label.textContent = 'Copied!';
                        setTimeout(() => { label.textContent = 'Copy'; }, 1600);
                    } catch (e) {
                        label.textContent = 'Press Ctrl+C';
                        setTimeout(() => { label.textContent = 'Copy'; }, 1600);
                    }
                });
            })();
        </script>
    @endpush
@endsection
