<?php

namespace Coleus\Support\Services\Concerns;

use LogicException;

trait CanBeOption
{
    // protected string $optionResource;

    /**
     * @throws \Throwable
     */
    public function options(): mixed
    {
        throw_unless(
            isset($this->optionResource),
            new LogicException(static::class . ' must define $optionResource property.')
        );

        $resource = $this->defaultQuery()->get();

        if (method_exists($this->optionResource, 'collectionWithNull')) {
            return $this->optionResource::collectionWithNull($resource);
        }

        return $this->optionResource::collection($resource);
    }
}
