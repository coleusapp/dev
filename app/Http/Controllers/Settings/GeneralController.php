<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GeneralSettingsRequest;
// use App\Http\Requests\Settings\ProfileUpdateRequest;
// use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/General', [
            'timezones' => array_map(fn($tz) => ['label' => $tz, 'value' => $tz], timezone_identifiers_list()),
        ]);
    }

    public function update(GeneralSettings $settings, GeneralSettingsRequest $request): RedirectResponse
    {
        $settings->timezone = $request->input('timezone');

        $settings->save();

        return to_route('general.edit');
    }
}
