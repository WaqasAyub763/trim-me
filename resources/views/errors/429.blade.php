@extends('layouts.app')

@section('title', 'Rate limit reached — Trim')
@section('description', "You've created too many links this hour.")

@section('content')
    <section class="state" role="alert" aria-labelledby="rl-title">
        <div class="state__icon state__icon--warning" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <h1 class="state__title" id="rl-title">Rate limit reached</h1>
        <p class="state__body">
            You've created the maximum number of short links allowed
            this hour. Please wait a little while and try again.
        </p>
        <div class="state__code">
            Limit · 10 links / hour / IP
        </div>
        <div class="state__actions">
            <a class="btn btn--secondary" href="javascript:history.back()">Go back</a>
            <a class="btn btn--primary" href="{{ route('links.create') }}">
                Return to home
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </a>
        </div>
    </section>
@endsection
