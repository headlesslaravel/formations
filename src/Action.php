<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\LazyCollection;

class Action
{
    public $key;

    public $job;

    public $fields = [];

    public $ability;

    public $formation;

    public function init($key): self
    {
        $this->key = $key;

        return $this;
    }

    public static function make($key): self
    {
        return (new self())->init($key);
    }

    public function job($job): self
    {
        $this->job = $job;

        return $this;
    }

    public function fields($fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function can($ability): self
    {
        $this->ability = $ability;

        return $this;
    }

    public function setFormation($formation)
    {
        $this->formation = $formation;

        return $this;
    }

    public function batch($selected, array $parameters = [], array $fields = [])
    {
        $query = $this->queryUsing($selected, $parameters);

        $batch = Bus::batch([])
            ->allowFailures()
            ->dispatch();

        $query->cursor()
            ->chunk(1000)
            ->each(function (LazyCollection $models) use ($batch, $fields) {
                foreach ($models as $model) {
                    $batch->add(new $this->job($model, $fields));
                }
            });

        return $batch;
    }

    public function validate(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $rules["fields.$field->key"] = $field->rules;
        }

        return Request::instance()->validate($rules);
    }

    public function queryUsing($selected, array $parameters = [])
    {
        if (count($parameters)) {
            $this->formation->given($parameters);
        }

        $model = app($this->formation->model);
        $query = $this->formation->builder();

        if (is_int($selected)) {
            $query = $query->where($model->getKeyName(), $selected);
        } elseif (is_array($selected)) {
            $query = $query->whereIn($model->getKeyName(), $selected);
        }

        return $query;
    }
}
