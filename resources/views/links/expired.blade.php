@extends('layouts.app')

@section('title', "Link expired — trim/{$link->short_code}")
@section('description', 'This short link has expired.')

@section('content')
    <section class="state" role="alert" aria-labelledby="expired-title">
        <div class="state__icon state__icon--warning" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <h1 class="state__title" id="expired-title">This link has expired</h1>
        <p class="state__body">
            The owner set an expiry date on this short link, and that date
            has now passed. The destination URL is no longer reachable
            through Trim.
        </p>
        <div class="state__code">
            {{ request()->getHost() }}<span class="slash">/</span><span class="code">{{ $link->short_code }}</span>
        </div>
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
