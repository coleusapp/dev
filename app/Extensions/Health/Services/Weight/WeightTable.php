<?php

namespace App\Extensions\Health\Services\Weight;

use App\Extensions\Health\Models\Weight;
use App\Packages\Table\Searchable;
use App\Packages\Table\Sortable;
use App\Packages\Table\Table;
use Illuminate\Database\Eloquent\Builder;

class WeightTable extends Table
{
    use Searchable;
    use Sortable;

    public static function query()
    {
        return Weight::query()
            ->when(static::hasSearchQuery(), fn ($query) => static::searchQuery($query))
            ->when(static::hasSortQuery(), fn ($query) => static::sortQuery($query))
            ->orderBy('created_at', 'desc');
    }

    protected static function searchQuery(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->orWhere('weight', request(static::$searchQuery))
                ->orWhereDate('date', request(static::$searchQuery))
                ->orWhereDay('date', request(static::$searchQuery))
                ->orWhereMonth('date', request(static::$searchQuery))
                ->orWhereYear('date', request(static::$searchQuery))
                ->orWhereTime('date', request(static::$searchQuery));
        });
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     sort: array<array{label: string, value: string}>
     * }>
     */
    public static function columns(): array
    {
        return [
            [
                'label' => 'Date',
                'value' => 'date_string',
                'sort' => [
                    ['label' => 'Asc', 'value' => 'date'],
                    ['label' => 'Desc', 'value' => '-date'],
                ],
            ],
             [
                'label' => 'Weight',
                 'value' => 'weight',
                'sort' => [
                    ['label' => 'Asc', 'value' => 'weight'],
                    ['label' => 'Desc', 'value' => '-weight']
                ],
            ],
        ];
    }

    protected static function sortQuery(Builder $query): Builder
    {
        return $query->orderBy('weight', 'desc');
    }
}