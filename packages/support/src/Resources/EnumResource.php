<?php

namespace Coleus\Support\Resources;

use Coleus\Support\Concerns\NullableResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnumResource extends JsonResource
{
    use NullableResourceCollection;

    public function toArray(Request $request): array
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->value,
        ];
    }
}
