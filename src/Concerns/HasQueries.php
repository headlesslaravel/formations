<?php

namespace HeadlessLaravel\Formations\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasQueries
{
    /**
     * Adjust index method query.
     *
     * @param mixed $query
     */
    public function indexQuery($query):void
    {
        //
    }

    /**
     * Adjust show method query.
     *
     * @param mixed $query
     */
    public function showQuery($query):void
    {
        //
    }

    /**
     * Adjust edit method query.
     *
     * @param mixed $query
     */
    public function editQuery($query):void
    {
        //
    }

    /**
     * Adjust update method query.
     *
     * @param mixed $query
     */
    public function updateQuery($query):void
    {
        //
    }

    /**
     * Adjust restore method query.
     *
     * @param mixed $query
     */
    public function restoreQuery($query):void
    {
        //
    }

    /**
     * Adjust destroy method query.
     *
     * @param mixed $query
     */
    public function destroyQuery($query):void
    {
        //
    }

    /**
     * Adjust force delete method query.
     *
     * @param mixed $query
     */
    public function forceDeleteQuery($query):void
    {
        //
    }

    /**
     * Call the proper query callback.
     *
     * @param string $method
     * @param mixed $query
     */
    public function queryCallback(string $method, Builder $query):void
    {
        $method = $method . 'Query';

        $this->$method($query);
    }
}
