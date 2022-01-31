<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Str;

class Field
{
    public $key;

    public $internal;

    public $label;

    public $rules;

    public $relationColumn;

    public $multiple = false;

    public function init($key, $internal = null): self
    {
        if (!is_null($internal) && str_contains($internal, '.')) {
            $this->relationColumn = Str::afterLast($internal, '.');
            $internal = Str::before($internal, '.');
        } elseif (is_null($internal) && Str::contains($key, '*')) {
            $this->multiple = true;
            $this->relationColumn = Str::afterLast($key, '.');
            if ($this->relationColumn === '*') {
                $this->relationColumn = null;
            }
            $internal = Str::before($key, '.');
            $key = Str::before($key, '.');
        } elseif (is_null($internal) && str_contains($key, '.')) {
            $this->relationColumn = Str::afterLast($key, '.');
            $internal = Str::before($key, '.');
            $key = Str::before($key, '.');
        } elseif (is_null($internal) && Str::contains($key, ' ')) {
            $internal = Str::snake($key);
        } elseif (is_null($internal)) {
            $internal = Str::lower($key);
        }

        $this->key = $key;
        $this->internal = $internal;

        return $this;
    }

    public static function make($key, $internal = null): self
    {
        return (new self())->init($key, $internal);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
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

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function toArray()
    {
        return [
            'key'      => $this->key,
            'internal' => $this->internal,
            'rules'    => $this->rules,
        ];
    }
}
