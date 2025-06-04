<?php

namespace Coleus\Support\Commands\Concerns;

use function Laravel\Prompts\select;

trait CanAskForPackage
{
    protected function askForPackage(?string $package): string
    {
        return (string) str($package ?? select(
            label: 'What is the package name?',
            options: [
                'health' => 'Health',
            ],
        ));
    }
}