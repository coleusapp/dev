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
        $files = Storage::allFiles('music');

        return Inertia::render('Dashboard', [
            'files' => $files,
        ]);
    }
}