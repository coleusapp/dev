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
     * @return LengthAwarePaginator<int, TModel> | LengthAwarePaginator<int, Model>
     */
    public function index(): LengthAwarePaginator
    {
        return $this->defaultQuery()->paginate();
    }

    /**
     * @return Builder<TModel> | Builder<Model>
     */
    public function defaultQuery(): Builder
    {
        return $this->model::query()->orderBy('created_at', 'desc');
    }

    /**
     * @return ?TModel
     */
    public function store(mixed $payload): ?Model
    {
        return method_exists($this, 'save')
            ? $this->save($payload)
            : $this->model::create($this->data::from($payload)->toArray());
    }

    /**
     * @param  TModel  $model
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
     */
    public function destroy(mixed $model): ?bool
    {
        return $model->delete();
    }

    /**
     * @param  TModel  $model
     */
    public function restore(mixed $model): bool
    {
        return $model->restore();
    }

    /**
     * @param  TModel  $model
     */
    public function forceDelete(mixed $model): ?bool
    {
        return $model->forceDelete();
    }

    /**
     * For soft-deleting models. toBase() keeps the global scopes (e.g. UserScope);
     * the builder's forceDelete() would skip them and delete every user's rows.
     */
    public function emptyTrash(): int
    {
        return $this->model::query()->onlyTrashed()->toBase()->delete();
    }
}
