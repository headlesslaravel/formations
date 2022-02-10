<?php

namespace HeadlessLaravel\Formations\Fields;

class Textarea extends Field
{
    public function rows(int $rows): self
    {
        return $this->props(['rows' => $rows]);
    }

    public function index()
    {
        $this->component('Textarea');

        $this->props(['limit' => 10]);
    }

    public function create()
    {
        $this->rules(['required']);

        $this->component('Textarea');

        $this->props(['rows' => 5]);
    }

    public function edit()
    {
        $this->rules(['required']);

        $this->component('Textarea');

        $this->props(['rows' => 5]);
    }
}
