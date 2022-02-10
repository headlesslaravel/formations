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

        $this->props(['url' => $this->getFormationUrl()]);
    }

    public function edit()
    {
        $this->rules(['required', $this->existsRule()]);

        $this->component('Picker');

        $this->props(['url' => $this->getFormationUrl()]);
    }

    private function getFormationUrl(): string
    {
        return route($this->formation->guessResourceName().'.index');
    }

    private function existsRule(): string
    {
        return "exists:{$this->model->getTable()},{$this->model->getKeyName()}"; // exists:users,id
    }
}