<?php

namespace HeadlessLaravel\Formations\Fields;

use HeadlessLaravel\Formations\Formation;
use Illuminate\Support\Str;

class Field
{
    public $key;

    public $internal;

    public $label;

    public $component;

    public $rules;

    public $props = [];

    public $relation;

    public $relationColumn;

    public $formation;

    public $model;

    public function render(Formation $formation, string $method): self
    {
        $this->formation = $formation;

        $this->model = app($formation->model);

        if (method_exists($this, $method)) {
            $this->$method();
        }

        return $this;
    }

    public function init($key, $internal = null): self
    {
        if (!is_null($internal) && Str::contains($internal, '.')) {
            $this->relationColumn = Str::afterLast($internal, '.');
            $this->relation = Str::beforeLast($internal, '.');
        } elseif (is_null($internal) && Str::contains($key, '.')) {
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

    public static function make($key, $internal = null): static
    {
        $field = new static();

        $field->init($key, $internal);

        return $field;
    }

    public function component($component): self
    {
        $this->component = $component;

        return $this;
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
        $this->props = array_merge($props, $this->props);

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
