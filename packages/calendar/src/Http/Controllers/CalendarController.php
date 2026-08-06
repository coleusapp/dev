<?php

namespace Coleus\Calendar\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function year(): Response
    {
        return Inertia::render('Year');
    }

    public function month(): Response
    {
        return Inertia::render('Month');
    }

    public function week(): Response
    {
        return Inertia::render('Week');
    }

    public function day(): Response
    {
        return Inertia::render('Day');
    }
}
