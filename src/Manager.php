<?php

namespace HeadlessLaravel\Formations;

use HeadlessLaravel\Formations\Exceptions\UnregisteredFormation;
use HeadlessLaravel\Formations\Http\Controllers\SeekerController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Manager
{
    /**
     * The current resource.
     *
     * @var
     */
    protected $current;

    /**
     * The resources.
     *
     * @var array
     */
    protected $resources = [];

    /**
     * The global search endpoints.
     *
     * @var array
     */
    protected $seekers = [];

    /**
     * Retrieve all resources.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->resources;
    }

    /**
     * Register a new resource.
     *
     * @param array $resource
     */
    public function register(array $resource)
    {
        if ($resource['parent'] && is_null($this->resource($resource['parent']))) {
            throw new UnregisteredFormation("Unknown parent formation: {$resource['parent']}");
        }

        $this->resources[] = $resource;
    }

    /**
     * Find a resource by key.
     *
     * @param $key
     *
     * @return ?array
     */
    public function resource($key): ?array
    {
        foreach ($this->resources as $resource) {
            if ($resource['resource'] === $key) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * The current formation object.
     *
     * @return Formation|null
     */
    public function formation($key = null): ?Formation
    {
        if (is_null($key)) {
            return app(Arr::get($this->current(), 'formation'));
        }

        foreach ($this->resources as $resource) {
            if ($resource['resource'] === $key) {
                return app($resource['formation']);
            }
        }

        return null;
    }

    /**
     * The current resource settings.
     *
     * @return mixed|null
     */
    public function current()
    {
        if ($this->current) {
            return $this->current;
        }

        $name = Request::route()->getName();

        foreach ($this->resources as $resource) {
            foreach ($resource['routes'] as $route) {
                if ($route['key'] === $name) {
                    $this->current = $resource;

                    return $resource;
                }
            }
        }

        abort(500, "No resource with route name: $name");
    }

    public function seeker(string $endpoint, array $formations): self
    {
        Route::get($endpoint, [SeekerController::class, 'index']);

        $this->seekers[$endpoint] = $formations;

        return $this;
    }

    public function getSeekerFormations(): array
    {
        $prefix = Request::route()->getPrefix();
        $endpoint = Request::route()->uri();
        $endpoint = Str::after($endpoint, "$prefix/");

        return Arr::get($this->seekers, $endpoint, []);
    }

    public function hasParent()
    {
        return Arr::get($this->current(), 'parent') != null;
    }
}
