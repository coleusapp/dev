<?php

namespace Coleus\Support\Services;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TData of \Spatie\LaravelData\Data
 */
abstract class Service
{
    /**
     * @var class-string<TModel>
     */
    abstract protected $model {
        get;
        set;
    }

    /**
     * @var class-string<TModel>
     */
    abstract protected $data {
        get;
        set;
    }

    public function index(): LengthAwarePaginator
    {
        return $this->model::orderBy('created_at', 'desc')
            ->paginate();
    }

    public function store(mixed $payload)
    {
        $data = $this->data::from($payload);

        return $this->model::create($data->toArray());
    }

    public function update(mixed $model, mixed $payload): bool
    {
        $data = $this->data::from($payload);

        return $model->update($data->toArray());
    }

    public function destroy(mixed $model): bool
    {
        return $model->delete();
    }
}
