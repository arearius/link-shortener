<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    /**
     * Resolve a short code, record the click and redirect to the original URL.
     */
    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $link = Link::where('code', $code)->firstOrFail();

        $link->registerClick($request);

        return redirect()->away($link->original_url);
    }
}
