<?php

namespace Coleus\Support\Resources;

use Coleus\Support\Concerns\NullableResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string|null $value
 * @method string getLabel()
 */
class EnumResource extends JsonResource
{
    use NullableResourceCollection;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->value,
        ];
    }
}
