<?php

namespace HeadlessLaravel\Formations;

use HeadlessLaravel\Formations\Exceptions\ReservedException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;

class Includes
{
    public $key;

    public $path;

    public $scope;

    public $scopeValue;

    public $rules = [];

    public $allowed;

    public $resource;

    public $aggregate;

    public $aggregateValue;

    public function apply($query)
    {
        //
    }

    public function isActive():bool
    {
        if(! Request::filled('includes')) {
            return false;
        }

        $includes = explode(',', Request::input('includes'));

        return in_array($this->key, $includes);
    }

    public static function make($key, $path = null)
    {
        return (new self)->init($key, $path);
    }

    public function init($key, $path = null): self
    {
        $this->key = $key;

        if(is_null($path)) {
            $this->path = $key;
        }

        return $this;
    }

    public function scope($scope, $value = null):self
    {
        $this->scope = $scope;

        $this->scopeValue = $value;

        return $this;
    }

    public function rule($requestKey, $rules):self
    {
        $this->rules[$requestKey] = $rules;

        return $this;
    }

    public function can($ability, $arguments = []): self
    {
        $this->allowed = Gate::allows($ability, $arguments);

        return $this;
    }

    public function resource($resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function count()
    {
        return $this->aggregate('count');
    }

    public function avg($column)
    {
        return $this->aggregate('avg', $column);
    }

    public function sum($column)
    {
        return $this->aggregate('sum', $column);
    }

    public function min($column)
    {
        return $this->aggregate('min', $column);
    }

    public function max($column)
    {
        return $this->aggregate('max', $column);
    }

    public function aggregate($aggregate, $value = null)
    {
        $this->aggregate = $aggregate;

        $this->aggregateValue = $value;

        return $this;
    }
}
