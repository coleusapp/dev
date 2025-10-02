<?php

namespace Coleus\Support\Services;

use Illuminate\Database\Eloquent\Model;
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
        if (method_exists($this, 'save')) {
            return $this->save($payload);
        }

        return $this->model::create($this->data::from($payload)->toArray());
    }

    /**
     * @param  mixed  $model
     * @param  mixed  $payload
     * @return bool | TModel
     */
    public function update(mixed $model, mixed $payload): Model|bool
    {
        if (method_exists($this, 'save')) {
            return $this->save($payload, $model);
        }

        return $model->update($this->data::from($payload)->toArray());
    }

    public function destroy(mixed $model): bool
    {
        return $model->delete();
    }
}
