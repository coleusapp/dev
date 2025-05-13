<?php

namespace App\Packages\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnumResource extends JsonResource
{
    public $addNull;

    public function toArray(Request $request): array
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->value,
        ];
    }

    public static function collectionWithNull($resource)
    {
        $this->addNull = true;

        parent::collection($resource);
    }
}
