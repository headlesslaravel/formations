<?php

namespace HeadlessLaravel\Formations\Fields;

class Select extends Field
{
    public function options($options)
    {
        $this->props(['options' => $options]);
    }

    public function index()
    {
        $this->component('Text');
    }

    public function create()
    {
        $this->component('Select');
    }

    public function edit()
    {
        $this->component('Select');
    }
}
