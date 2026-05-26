@extends('layouts.app')

@section('title', 'Link not found — Trim')
@section('description', "We couldn't find a short link with that code.")

@section('content')
    <section class="state" role="alert" aria-labelledby="nf-title">
        <div class="state__icon state__icon--danger" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <h1 class="state__title" id="nf-title">Link not found</h1>
        <p class="state__body">
            We couldn't find a short link with that code. It may have been
            mistyped, deleted, or never existed in the first place.
        </p>
        @isset($short_code)
            <div class="state__code">
                {{ request()->getHost() }}<span class="slash">/</span><span class="code">{{ $short_code }}</span>
            </div>
        @endisset
        <div class="state__actions">
            <a class="btn btn--secondary" href="{{ route('links.create') }}">Go back</a>
            <a class="btn btn--primary" href="{{ route('links.create') }}">
                Create a new link
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </a>
        </div>
    </section>
@endsection
