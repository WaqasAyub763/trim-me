@extends('layouts.app')

@section('title', 'Create a short link — Trim')
@section('page_class', 'page--narrow')

@section('content')
    <div class="page-head">
        <div class="page-head__eyebrow">Link shortener</div>
        <h1 class="page-head__title">Create a short link</h1>
        <p class="page-head__subtitle">
            Paste any URL, optionally set an expiry, and we'll give you a
            six-character short link with built-in click analytics.
        </p>
    </div>

    <section class="card" aria-labelledby="create-card-title">
        <header class="card__header">
            <div>
                <h2 class="card__title" id="create-card-title">New link</h2>
                <p class="card__subtitle">All fields are validated server-side.</p>
            </div>
            <span class="badge">Rate limit · 10 / hour</span>
        </header>

        <form action="{{ route('links.store') }}" method="POST" novalidate id="create-form">
            @csrf
            <div class="card__body">
                <div class="form">
                    <div class="field">
                        <div class="field__label-row">
                            <label class="field__label" for="original_url">Destination URL</label>
                            <span class="field__counter tnum" id="url-counter">
                                {{ mb_strlen(old('original_url', '')) }} / 2048
                            </span>
                        </div>
                        <input
                            class="input input--mono @error('original_url') input--error @enderror"
                            type="url"
                            id="original_url"
                            name="original_url"
                            value="{{ old('original_url') }}"
                            placeholder="https://example.com/your/long/path"
                            maxlength="2048"
                            autocomplete="off"
                            spellcheck="false"
                            required
                        >
                        @error('original_url')
                            <p class="field__hint" style="color: var(--danger-fg);">{{ $message }}</p>
                        @else
                            <p class="field__hint">Must be a valid, fully-qualified URL. Maximum 2,048 characters.</p>
                        @enderror
                    </div>

                    <div class="field">
                        <div class="field__label-row">
                            <label class="field__label" for="expires_at">
                                Expires<span class="field__optional">— optional</span>
                            </label>
                        </div>
                        <input
                            class="input input--mono"
                            type="datetime-local"
                            id="expires_at"
                            name="expires_at"
                            value="{{ old('expires_at') }}"
                            min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                        >
                        @error('expires_at')
                            <p class="field__hint" style="color: var(--danger-fg);">{{ $message }}</p>
                        @else
                            <p class="field__hint">Leave blank for a permanent link. Otherwise the short URL stops working after this moment.</p>
                        @enderror
                    </div>
                </div>
            </div>

            <footer class="card__footer">
                <span class="muted" style="font-size: var(--t-md);">
                    By creating a link you agree it will be publicly resolvable.
                </span>
                <div class="row">
                    <button class="btn btn--ghost" type="reset">Clear</button>
                    <button class="btn btn--primary" type="submit">
                        Shorten link
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </button>
                </div>
            </footer>
        </form>
    </section>

    <section aria-labelledby="how-title" style="margin-top: var(--s-9);">
        <h2 id="how-title" style="font-size: var(--t-base); font-weight: 600; color: var(--fg-muted); margin: 0; text-transform: uppercase; letter-spacing: 0.06em;">
            How it works
        </h2>
        <div class="steps">
            <article class="step">
                <span class="step__num">1</span>
                <h3 class="step__title">A random short code</h3>
                <p class="step__body">We mint a 6-character code (a–z, A–Z, 0–9) and check it's not already taken before saving.</p>
            </article>
            <article class="step">
                <span class="step__num">2</span>
                <h3 class="step__title">Instant 302 redirect</h3>
                <p class="step__body">Visitors get a redirect immediately. Click logging happens after the response is flushed.</p>
            </article>
            <article class="step">
                <span class="step__num">3</span>
                <h3 class="step__title">Private analytics</h3>
                <p class="step__body">Each visit logs IP, user agent, and referer — viewable on a per-link stats page.</p>
            </article>
        </div>
    </section>

    @push('scripts')
        <script>
            (function () {
                const input = document.getElementById('original_url');
                const counter = document.getElementById('url-counter');
                if (!input || !counter) return;
                const update = () => {
                    counter.textContent = `${input.value.length} / 2048`;
                };
                input.addEventListener('input', update);
                update();
            })();
        </script>
    @endpush
@endsection
