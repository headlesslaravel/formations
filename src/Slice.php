<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Slice
{
    /**
     * The internal key.
     *
     * @var
     */
    public $key;

    /**
     * The route name.
     *
     * @var
     */
    public $internal;

    /**
     * The filters name.
     *
     * @var
     */
    public $filters = [];

    /**
     * The query callbacks.
     *
     * @var array
     */
    public $queries = [];

    /**
     * The formation object.
     *
     * @var Formation
     */
    public $formation;

    /**
     * Make a filter instance.
     *
     * @param $public
     * @param $internal
     *
     * @return Slice
     */
    public static function make($public, $internal = null): self
    {
        return (new self())->init($public, $internal);
    }

    /**
     * Add the filter key.
     *
     * @param $public
     * @param $internal
     *
     * @return $this
     */
    protected function init($public, $internal = null): self
    {
        if (is_null($internal)) {
            $internal = Str::slug($public);
        }

        $this->key = $public;
        $this->internal = $internal;

        return $this;
    }

    /**
     * @param array $filter
     *
     * @return $this
     */
    public function filter($filter): self
    {
        $this->filters = $filter;

        return $this;
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function query($callback): self
    {
        $this->queries[] = $callback;

        return $this;
    }

    public function applyQuery($query)
    {
        foreach ($this->queries as $callback) {
            $query = $callback($query);
        }

        return $query;
    }

    public function apply()
    {
        if (count($this->filters)) {
            // allow sort & sort-desc overriding slices
            if (Request::has('sort')) {
                unset($this->filters['sort-desc']);
                unset($this->formation->defaults['sort-desc']);
            } elseif (Request::has('sort-desc')) {
                unset($this->filters['sort']);
                unset($this->formation->defaults['sort']);
            }

            Request::merge($this->filters);
        }

        if (count($this->queries)) {
            $this->formation->where(function ($query) {
                $this->applyQuery($query);
            });
        }

        return $this;
    }

    public function setFormation($formation)
    {
        $this->formation = $formation;

        return $this;
    }

    private function getRoute(): string
    {
        return $this->formation->guessResourceName().'.slices.'.$this->internal;
    }

    public function meta(): array
    {
        return [
            'display'   => $this->key,
            'link'      => route($this->getRoute()),
            'active'    => Route::currentRouteName() == $this->getRoute(),
        ];
    }
}
