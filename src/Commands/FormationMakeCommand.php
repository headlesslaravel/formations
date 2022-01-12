<?php

namespace HeadlessLaravel\Formations\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class FormationMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:formation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new list request class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Formation';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('formation.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     *
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim("stubs/$stub", '/')))
            ? $customPath
            : __DIR__.'/../../stubs/'.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $model = $this->option('model')
            ? $this->option('model')
            : $this->qualifyModel($this->guessModelName($name));

        $replace = [
            '{{ model }}'     => '\\'.$model,
            '{{ namespace }}' => $this->getDefaultNamespace($this->rootNamespace()),
        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * Guess the model name from the Factory name or return a default model name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function guessModelName($name)
    {
        if (Str::endsWith($name, 'Formation')) {
            $name = substr($name, 0, -9);
        }

        $rootNamespace = $this->rootNamespace();
        $modelName = $this->qualifyModel(Str::after($name, $this->getDefaultNamespace(trim($rootNamespace, '\\'))));
        $modelFilePath = app_path('Models/'.class_basename($modelName).'.php');
        if (class_exists($modelName) or file_exists($modelFilePath)) {
            return $modelName;
        }

        if (is_dir(app_path('Models/'))) {
            return $this->rootNamespace().'Models\Model';
        }

        return $this->rootNamespace().'Model';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Formations';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['model', null, InputOption::VALUE_OPTIONAL, 'Specify a Model to be used for the Formation'],
        ];
    }
}
