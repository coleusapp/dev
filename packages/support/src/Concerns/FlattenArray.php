<?php

namespace Coleus\Support\Concerns;

/**
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait FlattenArray
{
    public function flatten($dataKey, $arrayKey = 'id'): array
    {
        return array_column($this->validated($dataKey) ?? [], $arrayKey);
    }
}
