<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Str;

class Field
{
    public $key;

    public $internal;

    public $label;

    public $rules;

    public $props;

    public $type;

    public $component;

    public $relation;

    public $relationColumn;

    public function init($key, $internal = null): self
    {
        if (!is_null($internal) && str_contains($internal, '.')) {
            $this->relationColumn = Str::afterLast($internal, '.');
            $this->relation = Str::beforeLast($internal, '.');
        } elseif (is_null($internal) && str_contains($key, '.')) {
            $this->relationColumn = Str::afterLast($key, '.');
            $this->relation = Str::before($key, '.');
            $this->internal = $key;
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

    public function props(array $props): self
    {
        $this->props = $props;

        return $this;
    }

    public function as($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function component($component): self
    {
        $this->component = $component;

        return $this;
    }

    public function isRelation(): bool
    {
        return !is_null($this->relationColumn);
    }

    public function toArray(): array
    {
        return [
            'key'      => $this->key,
            'internal' => $this->internal,
            'rules'    => $this->rules,
        ];
    }
}
