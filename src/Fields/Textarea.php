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
        $this->component('Text');

        $this->props(['limit' => 10]);
    }

    public function create()
    {
        $this->component('Textarea');

        $this->props(['rows' => 5]);
    }

    public function edit()
    {
        $this->component('Textarea');

        $this->props(['rows' => 5]);
    }
}
