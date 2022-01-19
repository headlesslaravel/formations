<?php

namespace HeadlessLaravel\Formations;

class Field
{
    public $key;

    public $internal;

    public $rules;

    public $relation;

    public function init($key, $internal = null): self
    {
        $this->key = $key;
        $this->internal = $internal;

        return $this;
    }

    public static function make($key, $internal = null): self
    {
        return (new self())->init($key, $internal);
    }

    public function rules($rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function relation($display = 'id'): self
    {
        $this->relation = $display;

        return $this;
    }

    public function isRelation(): bool
    {
        return !is_null($this->relation);
    }
}
