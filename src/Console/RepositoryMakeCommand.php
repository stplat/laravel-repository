<?php

namespace Stsp\LaravelRepository\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RepositoryMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:repository';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);
        $entity = str_replace('Repository', '', $this->argument('name'));

        $migrate = $this->option('m');
        $migrateAndSeed = $this->option('ms');
        $migrate = $migrate || $migrateAndSeed ? ' -m' : '';

        if ($migrateAndSeed) {
            Artisan::call('make:seeder ' . $entity . 'Seeder');
        }

        Artisan::call('make:model ' . $entity . $migrate);
        return str_replace(
            ['{{ class }}', '{{ entity }}'],
            [$this->argument('name'), $entity],
            $stub
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../stubs/repository.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Repositories';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the repository.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['m', null, InputOption::VALUE_NONE, 'Creating a database migration', null],
            ['ms', null, InputOption::VALUE_NONE, 'Creating a database migration and seed', null],
        ];
    }
}
