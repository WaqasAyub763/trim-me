<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Persists a click event for a {@see Link} after the HTTP response has been
 * flushed to the visitor — keeping the redirect itself fast.
 *
 * Called from the controller's `terminating` callback, never directly on the
 * critical path of the redirect.
 */
class ClickRecorder
{
    /**
     * Record one visit. Wraps the log insert and counter increment in a single
     * transaction so the two are always consistent.
     *
     * Swallows DB exceptions on purpose — a logging failure must never surface
     * to a visitor whose redirect has already succeeded.
     */
    public function record(Link $link, Request $request): void
    {
        $ip      = $request->ip() ?? '0.0.0.0';
        $agent   = $request->userAgent();
        $referer = $request->headers->get('referer');

        try {
            DB::transaction(function () use ($link, $ip, $agent, $referer): void {
                $link->clickLogs()->create([
                    'ip_address' => $ip,
                    'user_agent' => $agent !== null ? mb_substr($agent, 0, 512) : null,
                    'referer'    => $referer !== null ? mb_substr($referer, 0, 2048) : null,
                    'clicked_at' => Carbon::now(),
                ]);

                $link->increment('click_count');
            });
        } catch (Throwable $e) {
            report($e);
        }
    }
}
