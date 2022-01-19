<?php

namespace HeadlessLaravel\Formations;

class Field
{
    public $key;

    public $rules;

    public $relation;

    public function init($key): self
    {
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $this->key = $segments[0];
            $this->relation($segments[1]);
        } else {
            $this->key = $key;
        }

        return $this;
    }

    public static function make($key): self
    {
        return (new self())->init($key);
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
