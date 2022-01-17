<?php

namespace HeadlessLaravel\Formations;

class Field
{
    public $key;

    public $rules;

    public $relation;

    public function init($key):self
    {
        $this->key = $key;

        return $this;
    }

    public static function make($key):self
    {
        return (new self())->init($key);
    }

    public function rules($rules):self
    {
        $this->rules = $rules;

        return $this;
    }

    public function relation($display = 'id'):self
    {
        $this->relation = $display;

        return $this;
    }

    public function isRelation():bool
    {
        return ! is_null($this->relation);
    }
}
