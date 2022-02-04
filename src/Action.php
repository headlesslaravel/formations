<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Facades\Gate;

class Action
{
    /** @var string */
    public $key;

    /** @var string */
    public $job;

    /** @var array */
    public $fields = [];

    /** @var string */
    public $after;

    /** @var string */
    public $afterEach;

    /** @var bool */
    public $allowed = true;

    /**
     * The formation object.
     *
     * @var Formation
     */
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

    public function after($after): self
    {
        $this->after = $after;

        return $this;
    }

    public function afterEach($afterEach): self
    {
        $this->afterEach = $afterEach;

        return $this;
    }

    public function can($ability, $arguments = []): self
    {
        $this->allowed = Gate::allows($ability, $arguments);

        return $this;
    }

    public function setFormation($formation)
    {
        $this->formation = $formation;

        return $this;
    }
}