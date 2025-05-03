<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Services\Weight\WeightTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('@health/Dashboard', [
            'weights' => WeightTable::records(),
        ]);
    }
}