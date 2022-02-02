<?php

namespace HeadlessLaravel\Formations;

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
}
