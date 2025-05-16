<?php

namespace App\Packages\Support\Resources;

use App\Packages\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class EnumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->value,
        ];
    }

    public static function collectionWithNull(mixed $resource): AnonymousResourceCollection
    {
        $resourceCollection = parent::collection($resource);

        $resourceCollection->collection->prepend(
            new static(new class implements HasLabel {
                public null $value = null;

                public function getLabel(): string|Htmlable|null
                {
                    return __('-- Select One --');
                }
            })
        );

        return $resourceCollection;
    }
}
