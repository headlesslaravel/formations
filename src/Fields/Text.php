<?php

namespace HeadlessLaravel\Formations\Fields;

class Text extends Field
{
    public function index()
    {
        $this->component('Text');
    }

    public function create()
    {
        $this->component('Text');
    }

    public function edit()
    {
        $this->component('Text');
    }
}
