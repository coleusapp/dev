<?php

namespace Coleus\Users\Services;

class UserService
{
    public function create(array $data)
    {
        return config('auth.providers.users.model')::create($data);
    }
}
