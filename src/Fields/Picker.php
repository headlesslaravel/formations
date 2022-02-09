<?php

namespace HeadlessLaravel\Formations\Fields;

class Picker extends FieldType
{
    public function render()
    {
        $this->field->rules(['required']);

        $this->field->component('FieldPicker');

        $this->field->props(['url' => $this->getFormationUrl()]);
    }

    public function getFormationUrl(): string
    {
        return route($this->formation->guessResourceName().'.index');
    }

    public function existsRule()
    {
        $model = app($this->formation->model);

        return "exists:{$model->getTable()},{$model->$this->getKeyName()},"; // exists:users,id
    }
}