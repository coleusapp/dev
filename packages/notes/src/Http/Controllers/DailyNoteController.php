<?php

namespace Coleus\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Coleus\Notes\Models\Note;
use Coleus\Notes\Services\DailyNotes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DailyNoteController extends Controller
{
    /**
     * Opens a day's note, making it first if there is none (O9.1). The
     * date is the browser's own "today" (its time zone), or any day.
     */
    public function store(Request $request, DailyNotes $daily): RedirectResponse
    {
        Gate::authorize('create', Note::class);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'body' => ['present', 'nullable', 'string', 'max:100000'],
        ]);

        $note = $daily->open(CarbonImmutable::createFromFormat('!Y-m-d', $data['date']), (string) ($data['body'] ?? ''));

        return to_route('notes.show', ['note' => $note]);
    }
}
