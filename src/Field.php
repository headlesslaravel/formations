<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Str;

class Field
{
    public $key;

    public $internal;

    public $rules;

    public $relation;

    public function init($key, $internal = null): self
    {
        if (is_null($internal) && str_contains($key, '.')) {
            $internal = $key;
            $key = Str::before($key, '.');
        }

        $this->key = $key;
        $this->internal = Str::beforeLast($internal, '.');
        $this->relation(Str::afterLast($internal, '.'));

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

    public function relation($display = null): self
    {
        if (!is_null($display)) {
            $this->relation = $display;
        }

        return $this;
    }

    public function isRelation(): bool
    {
        return !is_null($this->relation);
    }
}
