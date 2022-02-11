<?php

namespace HeadlessLaravel\Formations\Fields;

class Picker extends Field
{
    public function index()
    {
        $this->component('Text');
    }

    public function create()
    {
        $this->component('Picker');

        $this->props(['url' => $this->formation->url('index')]);
    }

    public function edit()
    {
        $this->component('Picker');

        $this->props(['url' => $this->formation->url('index')]);
    }

    public function exists(): self
    {
        return $this->whenRendering(function () {
            $this->rules(["exists:{$this->model->getTable()},{$this->model->getKeyName()}"]); // exists:users,id
        });
    }
}
