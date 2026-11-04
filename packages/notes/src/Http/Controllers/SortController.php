<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\SortRequest;
use Coleus\Notes\Preferences\NotePreferences;
use Illuminate\Http\RedirectResponse;

class SortController extends Controller
{
    /**
     * Sets how every list is ordered.
     */
    public function update(SortRequest $request): RedirectResponse
    {
        NotePreferences::save(['list.sort' => $request->validated('sort'), 'list.sort_direction' => $request->validated('direction') ?? 'desc']);

        return back();
    }
}
