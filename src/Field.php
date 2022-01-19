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
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $this->key = $segments[0];
            $this->relation($segments[1]);
        } else {
            $this->key = $key;
        }
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
