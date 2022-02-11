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
        $this->rules(['required', $this->existsRule()]);

        $this->component('Picker');

        $this->props(['url' => $this->formation->url('index')]);
    }

    public function edit()
    {
        $this->rules(['required', $this->existsRule()]);

        $this->component('Picker');

        $this->props(['url' => $this->formation->url('index')]);
    }

    private function existsRule(): string
    {
        return "exists:{$this->model->getTable()},{$this->model->getKeyName()}"; // exists:users,id
    }
}
