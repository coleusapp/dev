<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Coleus\Notes\Http\Requests\PreferencesRequest;
use Coleus\Notes\Preferences\NotePreferences;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Settings', [
            'definitions' => NotePreferences::DEFINITIONS,
            'preferences' => NotePreferences::all(),
        ]);
    }

    public function update(PreferencesRequest $request): RedirectResponse
    {
        NotePreferences::save($request->preferences());

        return back();
    }
}
