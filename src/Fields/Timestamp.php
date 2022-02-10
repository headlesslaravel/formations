<?php

namespace HeadlessLaravel\Formations\Fields;

class Timestamp extends Field
{
    public function index()
    {
        $this->component('Timestamp');
    }

    public function show()
    {
        $this->component('Timestamp');
    }

    public function create()
    {
        $this->component('DateTime');
    }

    public function edit()
    {
        $this->component('DateTime');
    }
}
