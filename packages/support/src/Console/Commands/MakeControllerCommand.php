<?php

namespace Coleus\Support\Console\Commands;

use Coleus\Support\Commands\Concerns\CanAskForPackage;
use Illuminate\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:coleus-controller', aliases: [
    'coleus:controller',
])]
class MakeControllerCommand extends Command
{
    use CanAskForPackage;

    protected $description = 'Create a new controller class';

    protected $signature = 'make:coleus-controller {name?} {package?}';

    public function handle()
    {
        $controller = (string) str($this->argument('name') ?? text(
            label: 'What is the controller name?',
            placeholder: 'CategoryController',
            required: true,
        ))
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->replace('/', '\\');

        $package = $this->askForPackage($this->argument('name'));

        $controllerClass = (string) str($controller)->afterLast('\\');
        $controllerNamespace = str($controller)->contains('\\') ?
            (string) str($controller)->beforeLast('\\') :
            '';

        dd($package, $controllerClass, $controllerNamespace);
    }
}
