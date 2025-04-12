<?php

namespace App\Extensions\Health\Http\Controllers;

use App\Extensions\Health\Http\Requests\Weights\StoreRequest;
use App\Extensions\Health\Http\Requests\Weights\UpdateRequest;
use App\Extensions\Health\Http\Resources\WeightResource;
use App\Extensions\Health\Models\Weight;
use App\Extensions\Health\Services\Weight\WeightTable;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class WeightController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('@health/weights/Index', [
            'table' => [
                'records' => WeightResource::collection(WeightTable::query()->paginate()),
                'columns' => WeightTable::columns(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('@health/weights/Create', [
            'weight' => Weight::first()?->weight ?? 0,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $weight = Weight::create($request->all());

        return to_route('health.weights.edit', ['weight' => new WeightResource($weight)]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Weight $weight)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Weight $weight)
    {
        return Inertia::render('@health/weights/Edit', [
            'weight' => new WeightResource($weight),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Weight $weight)
    {
        $weight->update($request->all());

        return to_route('health.weights.edit', ['weight' => new WeightResource($weight)]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Weight $weight)
    {
        $weight->delete();

        return to_route('health.weights.index');
    }
}
