<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RedirectController extends Controller
{
    /**
     * Resolve a short code, return a 302 immediately for live links, and
     * delegate click logging to the {@see \App\Http\Middleware\LogClicks}
     * terminable middleware so the visitor never waits on a DB write.
     */
    public function __invoke(Request $request, string $short_code): RedirectResponse|Response
    {
        $link = Link::where('short_code', $short_code)->first();

        if ($link === null) {
            return response()->view('errors.404', ['short_code' => $short_code], 404);
        }

        if ($link->is_expired) {
            return response()->view('links.expired', ['link' => $link], 410);
        }

        // The `log.clicks` middleware picks this up in its terminate() hook.
        $request->attributes->set('logged_link', $link);

        return redirect()->away($link->original_url, 302);
    }
}
