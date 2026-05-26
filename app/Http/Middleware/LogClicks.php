<?php

namespace App\Http\Middleware;

use App\Models\Link;
use App\Services\ClickRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terminable middleware that defers the click-log write until after the
 * 302 redirect has been flushed to the visitor. The controller signals
 * intent by setting the `logged_link` request attribute.
 *
 * Unlike {@see \Illuminate\Foundation\Application::terminating()}, the
 * middleware's `terminate()` method fires exactly once per request — even
 * when the same application instance handles multiple requests in tests.
 */
class LogClicks
{
    public function __construct(
        private readonly ClickRecorder $recorder,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $link = $request->attributes->get('logged_link');

        if ($link instanceof Link) {
            $this->recorder->record($link, $request);
        }
    }
}
