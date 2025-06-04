<?php

namespace Coleus\Support;

use Coleus\Support\Console\Commands\MakeControllerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SupportServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('support')
            ->hasCommand(MakeControllerCommand::class);
    }
}
