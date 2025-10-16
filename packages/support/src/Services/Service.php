<?php

namespace Coleus\Support\Services;

use Illuminate\Database\Eloquent\Builder;
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
     * @var class-string<TData>
     */
    abstract protected $data {
        get;
        set;
    }

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, \Illuminate\Database\Eloquent\Model>
     */
    public function index(): LengthAwarePaginator
    {
        return $this->defaultQuery()->paginate();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function defaultQuery(): Builder
    {
        return $this->model::orderBy('created_at', 'desc');
    }

    /**
     * @param  mixed  $payload
     * @return mixed|TModel|bool
     */
    public function store(mixed $payload): mixed
    {
        if (method_exists($this, 'save')) {
            return $this->save($payload);
        }

        return $this->model::create($this->data::from($payload)->toArray());
    }

    /**
     * @param  TModel  $model
     * @param  mixed  $payload
     * @return mixed|bool|TModel
     */
    public function update(mixed $model, mixed $payload): mixed
    {
        if (method_exists($this, 'save')) {
            return $this->save($payload, $model);
        }

        return $model->update($this->data::from($payload)->toArray());
    }

    /**
     * @param  TModel  $model
     * @return bool|null
     */
    public function destroy(mixed $model): bool|null
    {
        return $model->delete();
    }
}
