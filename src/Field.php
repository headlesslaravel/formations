<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Str;

class Field
{
    public $key;

    public $internal;

    public $rules;

    public $relationColumn;

    public function init($key, $internal = null): self
    {
        if (is_null($internal) && str_contains($key, '.')) {
            $this->relationColumn = Str::afterLast($key, '.');
            $internal = Str::before($key, '.');
            $key = Str::before($key, '.');
        } elseif (is_null($internal)) {
            $internal = $key;
        }

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

    public function isRelation(): bool
    {
        return !is_null($this->relationColumn);
    }
}
