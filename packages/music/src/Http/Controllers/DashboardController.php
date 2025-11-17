<?php

namespace Coleus\Music\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Storage;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $files = collect(Storage::allFiles('music'))
            ->filter(function ($f) {
                return strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'mp3';
            })
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'files' => $files,
        ]);
    }
}