<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\EventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    /**
     * Resolve a short code, record the click and redirect to the original URL.
     */
    public function __invoke(Request $request, string $code, EventLogger $events): RedirectResponse
    {
        $link = Link::where('code', $code)->firstOrFail();

        $link->registerClick($request);

        $events->record('link.clicked', 'Short link visited', [
            'link_id' => $link->id,
            'code' => $link->code,
            'ip' => $request->ip(),
        ]);

        return redirect()->away($link->original_url);
    }
}
