<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Routing
{
    public $parent;

    public $resource;

    protected $routes = [];

    protected $formation;

    protected $prefix;

    protected $manager;

    protected $router;

    protected $routeTypes = [
        'index' => ['GET', 'HEAD'],
        'create' => ['GET', 'HEAD'],
        'show' => ['GET', 'HEAD'],
        'store' => 'POST',
        'edit' => ['GET', 'HEAD'],
        'update' => 'PUT',
        'destroy' => 'DELETE',
        'restore' => 'PUT',
        'force-delete' => 'DELETE',
    ];

    public function __construct(Manager $manager, Router $router)
    {
        $this->manager = $manager;

        $this->router = $router;

        $this->prefix = $this->router->getLastGroupPrefix();
    }

    public function setResource($resource)
    {
        $this->parent = Str::before($resource, '.');
        $this->resource = Str::after($resource, '.');

        if($this->parent === $this->resource) {
            $this->parent = null;
        }

        return $this;
    }

    public function setFormation($formation)
    {
        $this->formation = $formation;

        return $this;
    }

    public function setRoutes(array $routes = [])
    {
        $this->routes = $routes;

        return $this;
    }

    public function setPrefix($prefix = null):self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function make()
    {
        $routeTypes = empty($this->routes)
            ? $this->routeTypes
            : Arr::only($this->routeTypes, $this->routes);

        $endpoints = $this->endpoints();

        $output = [];

        foreach ($routeTypes as $name => $verb) {
            $output[] = [
                'verb' => $verb,
                'type' => $name,
                'endpoint' => $endpoints[$name],
                'with-trashed' => $this->withTrashed($name),
                'action' => $this->makeAction($name),
                'name' => $this->makeName($name),
                'key' => $this->makeKey($name),
            ];
        }

        return $output;
    }

    public function withTrashed($name):bool
    {
        return true; // todo: determine parent and child trash state
        return in_array($name, ['show', 'restore', 'force-delete']);
    }

    public function makeName($name)
    {
        if($this->parent) {
            return "$this->parent.$this->resource.$name";
        }

        return "$this->resource.$name";
    }

    public function makeKey($name):string
    {
        $output = "$this->resource.$name";

        if($this->parent) {
            $output = "$this->parent.$output";
        }

        if($this->prefix) {
            $output = "$this->prefix.$output";
        }

        return $output;
    }

    public  function makeAction($name): array
    {
        $name = Str::camel($name);

        if($this->parent) {
            return [app($this->formation)->nestedController, $name];
        }

        return [app($this->formation)->controller, $name];
    }

    public function create():self
    {
        $routes = $this->make();

        foreach ($routes as $route) {
            $this->router
                ->addRoute($route['verb'], $route['endpoint'], $route['action'])
                ->name($route['name'])
                ->withTrashed($route['with-trashed']);
        }

        $this->manager->register([
            'formation' => $this->formation,
            'resource' => $this->resource,
            'parent' => $this->parent,
            'resource_route_key' => $this->resourceRouteKey(),
            'parent_route_key' => $this->parentRouteKey(),
            'routes' => $routes,
        ]);

        return $this;
    }

    private function endpoints(): array
    {
        $key = $this->resourceRouteKey();

        $endpoints = [
            'index' => $this->resource,
            'create' => "$this->resource/new",
            'store' => "$this->resource/new",
            'show' => "$this->resource/{{$key}}",
            'edit' => "$this->resource/{{$key}}/edit",
            'update' => "$this->resource/{{$key}}/edit",
            'destroy' => "$this->resource/{{$key}}",
            'restore' => "$this->resource/{{$key}}/restore",
            'force-delete' => "$this->resource/{{$key}}/force-delete",
        ];

        if($this->parent) {
            foreach($endpoints as $name => $endpoint) {
                $endpoints[$name] = "$this->parent/{{$this->parentRouteKey()}}/$endpoint";
            }
        }

        return $endpoints;
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
}
