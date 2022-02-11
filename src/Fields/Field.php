<?php

namespace HeadlessLaravel\Formations\Fields;

use HeadlessLaravel\Formations\Formation;
use Illuminate\Support\Str;

class Field
{
    public $key;

    public $internal;

    public $label;

    public $component = 'Text';

    public $rules = [];

    public $props = [];

    public $relation;

    public $relationColumn;

    public $sortable = false;

    public $formation;

    public $model;

    protected $rendering = [];

    public function render(Formation $formation, string $method): self
    {
        $this->formation = $formation;

        $this->model = app($formation->model);

        if (method_exists($this, $method)) {
            $this->$method();
        }

        foreach ($this->props as $key => $value) {
            $this->props[$key] = value($value);
        }

        foreach ($this->rendering as $callback) {
            $callback();
        }

        if ($method === 'index') {
            $this->rules = [];
        }

        if (!$this->sortable) {
            $hasSortDefinition = collect($this->formation->sort())
                ->filter(function ($sort) {
                    return $sort->internal === $this->internal;
                })->count();

            $this->sortable($hasSortDefinition);
        }

        return $this;
    }

    public function whenRendering(callable $callable): self
    {
        $this->rendering[] = $callable;

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
        $this->rules = array_merge($rules, $this->rules);

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

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function meta(): array
    {
        return [
            'display'      => (string) $this->key,
            'key'          => $this->internal,
            'component'    => $this->component,
            'props'        => $this->props,
            'sortable'     => $this->sortable,
        ];
    }
}
