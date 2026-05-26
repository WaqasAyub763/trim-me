<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateLinkRequest;
use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class LinkController extends Controller
{
    public function __construct(
        private readonly ShortCodeGenerator $codes,
    ) {
    }

    public function create(): View
    {
        return view('links.home');
    }

    public function store(CreateLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $link = Link::create([
            'original_url' => $validated['original_url'],
            'short_code'   => $this->codes->generate(),
            'expires_at'   => isset($validated['expires_at'])
                ? Carbon::parse($validated['expires_at'])
                : null,
        ]);

        return redirect()
            ->route('links.show', ['short_code' => $link->short_code])
            ->with('status', 'link.created');
    }

    public function show(string $short_code): View
    {
        $link = Link::where('short_code', $short_code)->firstOrFail();

        return view('links.result', [
            'link' => $link,
            'justCreated' => session('status') === 'link.created',
        ]);
    }
}
