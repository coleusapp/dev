<?php

namespace Coleus\Settings;

class SettingsManager
{
    public function group(string $group): SettingsGroup
    {
        return new SettingsGroup($group);
    }
}
