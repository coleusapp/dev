<?php

namespace Coleus\Support\Concerns;

use Coleus\Support\Contracts\HasLabel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @mixin \Illuminate\Http\Resources\Json\JsonResource
 */
trait NullableResourceCollection
{
    public static function collectionWithNull(mixed $resource): AnonymousResourceCollection
    {
        $resourceCollection = parent::collection($resource);

        $resourceCollection->collection->prepend(
            new self(new class implements HasLabel {
                public null $value = null;

                public function getLabel(): string
                {
                    return __('-- Select One --');
                }
            })
        );

        return $resourceCollection;
    }
}
