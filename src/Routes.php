<?php

namespace HeadlessLaravel\Formations;

use HeadlessLaravel\Formations\Http\Controllers\ActionController;
use HeadlessLaravel\Formations\Http\Controllers\ExportController;
use HeadlessLaravel\Formations\Http\Controllers\ImportController;
use HeadlessLaravel\Formations\Http\Controllers\SliceController;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class Routes
{
    public $parent;

    public $resource;

    public $pivot = false;

    public $import = false;

    public $export = false;

    protected $types = [];

    protected $formation;

    protected $prefix;

    protected $manager;

    protected $router;

    public function __construct(Manager $manager, Router $router)
    {
        $this->manager = $manager;

        $this->router = $router;

        $this->prefix = $this->router->getLastGroupPrefix();
    }

    public function formation($formation)
    {
        $this->formation = $formation;

        return $this;
    }

    public function resource($resource): self
    {
        $this->parent = Str::before($resource, '.');
        $this->resource = Str::after($resource, '.');

        if ($this->parent === $this->resource) {
            $this->parent = null;
        }

        return $this;
    }

    public function asPivot(): self
    {
        $this->pivot = true;

        return $this;
    }

    public function asImport(): self
    {
        $this->import = true;

        return $this;
    }

    public function asExport(): self
    {
        $this->export = true;

        return $this;
    }

    public function setTypes(array $types = [])
    {
        $this->types['only'] = $types;

        return $this;
    }

    public function setPrefix($prefix = null): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function make()
    {
        return array_map(function ($endpoint) {
            return array_merge([
                'with-trashed' => $this->withTrashed($endpoint['type']),
                'action'       => $this->makeAction($endpoint['type']),
                'name'         => $this->makeName($endpoint['type']),
                'key'          => $this->makeKey($endpoint['type']),
            ], $endpoint);
        }, $this->endpoints());
    }

    public function withTrashed($name): bool
    {
        return true; // todo: determine parent and child trash state

        return in_array($name, ['show', 'restore', 'force-delete']);
    }

    public function makeName($name)
    {
        if ($this->import) {
            return "$this->resource.imports.$name";
        }

        if ($this->export) {
            return "$this->resource.exports.$name";
        }

        if ($this->parent) {
            return "$this->parent.$this->resource.$name";
        }

        return "$this->resource.$name";
    }

    public function makeKey($name): string
    {
        $output = "$this->resource.$name";

        if ($this->parent) {
            $output = "$this->parent.$output";
        }

        if ($this->prefix) {
            $output = "$this->prefix.$output";
        }

        return $output;
    }

    public function makeAction($name): array
    {
        $name = Str::camel($name);

        if ($this->import) {
            return [ImportController::class, $name];
        } elseif ($this->export) {
            return [ExportController::class, $name];
        } elseif ($this->pivot) {
            return [app($this->formation)->pivotController, $name];
        } elseif ($this->parent) {
            return [app($this->formation)->nestedController, $name];
        } else {
            return [app($this->formation)->controller, $name];
        }
    }

    public function create(): array
    {
        $routes = $this->make();

        foreach ($routes as $route) {
            $this->router
                ->addRoute($route['verb'], $route['endpoint'], $route['action'])
                ->name($route['name'])
                ->withTrashed($route['with-trashed'])
                ->defaults('formation', $this->formation);
        }

        $this->manager->register([
            'formation'          => $this->formation,
            'resource'           => $this->resource,
            'parent'             => $this->parent,
            'resource_route_key' => $this->resourceRouteKey(),
            'parent_route_key'   => $this->parentRouteKey(),
            'routes'             => $routes,
        ]);

        $this->registerNestedResources();

        return $routes;
    }

    public function endpoints(): array
    {
        if ($this->import) {
            $endpoints = $this->importEndpoints();
        } elseif ($this->export) {
            $endpoints = $this->exportEndpoints();
        } elseif ($this->pivot) {
            $endpoints = $this->pivotEndpoints();
        } elseif ($this->parent) {
            $endpoints = $this->nestedEndpoints();
        } else {
            $endpoints = $this->resourceEndpoints();
        }

        if (!$this->types) {
            return $endpoints;
        }

        if (isset($this->types['only']) and count($this->types['only'])) {
            $only = $this->types['only'];
            $endpoints = array_filter($endpoints, function ($endpoint) use ($only) {
                return in_array($endpoint['type'], $only);
            });
        }

        if (isset($this->types['except']) and count($this->types['except'])) {
            $except = $this->types['except'];
            $endpoints = array_filter($endpoints, function ($endpoint) use ($except) {
                return !in_array($endpoint['type'], $except);
            });
        }

        return $endpoints;
    }

    protected function sliceEndpoints(): array
    {
        $slices = app($this->formation)->slices();

        if (!count($slices)) {
            return [];
        }

        $routes = [];

        /** @var Slice $slice */
        foreach ($slices as $slice) {
            $routes[] = [
                'type'         => 'index',
                'verb'         => ['GET', 'HEAD'],
                'action'       => [SliceController::class, 'index'],
                'name'         => "$this->resource.slices.$slice->internal",
                'endpoint'     => "$this->resource/{$slice->internal}",
                'key'          => "$this->resource.slices.$slice->internal",
                'with-trashed' => false,
            ];
        }

        return $routes;
    }

    protected function actionEndpoints(): array
    {
        $actions = app($this->formation)->actions();

        if (!count($actions)) {
            return [];
        }

        $routes = [];

        /** @var Action $action */
        foreach ($actions as $action) {
            $routes[] = [
                'type'         => 'store',
                'verb'         => 'POST',
                'action'       => [ActionController::class, 'store'],
                'name'         => "$this->resource.actions.{$action->key}.store",
                'endpoint'     => "actions/$this->resource/{$action->key}",
                'key'          => "$this->resource.actions.{$action->key}.store",
                'with-trashed' => false,
            ];

            $routes[] = [
                'type'         => 'show',
                'verb'         => ['GET', 'HEAD'],
                'action'       => [ActionController::class, 'progress'],
                'name'         => "$this->resource.actions.{$action->key}.show",
                'endpoint'     => "actions/$this->resource/{$action->key}/{batchId}",
                'key'          => "$this->resource.actions.{$action->key}.show",
                'with-trashed' => false,
            ];
        }

        return $routes;
    }

    private function resourceEndpoints(): array
    {
        $key = $this->resourceRouteKey();

        return array_merge($this->sliceEndpoints(), $this->actionEndpoints(), [
            ['type' => 'index', 'verb' => ['GET', 'HEAD'], 'endpoint' => $this->resource],
            ['type' => 'create', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->resource/new"],
            ['type' => 'store', 'verb' => 'POST', 'endpoint' => "$this->resource/new"],
            ['type' => 'show', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->resource/{{$key}}"],
            ['type' => 'edit', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->resource/{{$key}}/edit"],
            ['type' => 'update', 'verb' => 'PUT', 'endpoint' => "$this->resource/{{$key}}/edit"],
            ['type' => 'destroy', 'verb' => 'DELETE', 'endpoint' => "$this->resource/{{$key}}"],
            ['type' => 'restore', 'verb' => 'PUT', 'endpoint' => "$this->resource/{{$key}}/restore"],
            ['type' => 'force-delete', 'verb' => 'DELETE', 'endpoint' => "$this->resource/{{$key}}/force-delete"],
        ]);
    }

    public function nestedEndpoints(): array
    {
        $p = $this->parentRouteKey();
        $r = $this->resourceRouteKey();

        return [
            ['type' => 'index', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->parent/{{$p}}/$this->resource"],
            ['type' => 'create', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->parent/{{$p}}/$this->resource/new"],
            ['type' => 'store', 'verb' => 'POST', 'endpoint' => "$this->parent/{{$p}}/$this->resource/new"],
            ['type' => 'show', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->parent/{{$p}}/$this->resource/{{$r}}"],
            ['type' => 'edit', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->parent/{{$p}}/$this->resource/{{$r}}/edit"],
            ['type' => 'update', 'verb' => 'PUT', 'endpoint' => "$this->parent/{{$p}}/$this->resource/{{$r}}/edit"],
            ['type' => 'destroy', 'verb' => 'DELETE', 'endpoint' => "$this->parent/{{$p}}/$this->resource/{{$r}}"],
            ['type' => 'restore', 'verb' => 'PUT', 'endpoint' => "$this->parent/{{$p}}/$this->resource/{{$r}}/restore"],
            ['type' => 'force-delete', 'verb' => 'DELETE', 'endpoint' => "$this->parent/{{$p}}/$this->resource/{{$r}}/force-delete"],
        ];
    }

    public function registerNestedResources()
    {
        $nestedResources = app($this->formation)->nested();

        if (count($nestedResources)) {
            foreach ($nestedResources as $routeKey => $nestedResource) {
                /** @var Formation $nestedFormation */
                $nestedFormation = app($nestedResource);
                if (is_int($routeKey)) {
                    $routeKey = $nestedFormation->guessResourceName();
                }
                static::formation($nestedResource)
                    ->resource($this->resource.'.'.$routeKey)->create();
            }
        }
    }

    private function pivotEndpoints(): array
    {
        $p = $this->parentRouteKey();
        $r = $this->resourceRouteKey();

        return [
            ['type' => 'count', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->parent/{{$p}}/$this->resource/count"],
            ['type' => 'index', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->parent/{{$p}}/$this->resource"],
            ['type' => 'show', 'verb' => ['GET', 'HEAD'], 'endpoint' => "$this->parent/{{$p}}/$this->resource/{{$r}}"],
            ['type' => 'sync', 'verb' => 'POST', 'endpoint' => "$this->parent/{{$p}}/$this->resource/sync"],
            ['type' => 'toggle', 'verb' => 'POST', 'endpoint' => "$this->parent/{{$p}}/$this->resource/toggle"],
            ['type' => 'attach', 'verb' => 'POST', 'endpoint' => "$this->parent/{{$p}}/$this->resource/attach"],
            ['type' => 'detach', 'verb' => 'DELETE', 'endpoint' => "$this->parent/{{$p}}/$this->resource/detach"],
        ];
    }

    private function importEndpoints(): array
    {
        return [
            ['type' => 'store', 'verb' => 'POST', 'endpoint' => "imports/$this->resource"],
            ['type' => 'create', 'verb' => 'GET', 'endpoint' => "imports/$this->resource"],
        ];
    }

    private function exportEndpoints(): array
    {
        return [
            ['type' => 'create', 'verb' => 'GET', 'endpoint' => "exports/$this->resource"],
        ];
    }

    public function makeRouteKey($key): string
    {
        return Str::of($key)->replace('-', '_')->singular();
    }

    public function parentRouteKey(): string
    {
        return $this->makeRouteKey($this->parent);
    }

    public function resourceRouteKey(): string
    {
        return $this->makeRouteKey($this->resource);
    }

    /**
     * @param array|string $types
     *
     * @return $this
     */
    public function only($types)
    {
        if (is_string($types)) {
            $types = [$types];
        }

        $this->types['only'] = $types;

        return $this;
    }

    /**
     * @param array|string $types
     *
     * @return $this
     */
    public function except($types)
    {
        if (is_string($types)) {
            $types = [$types];
        }

        $this->types['except'] = $types;

        return $this;
    }

    public function __destruct()
    {
        return $this->create();
    }
}
