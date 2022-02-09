<?php

namespace HeadlessLaravel\Formations\Fields;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Formation;

abstract class FieldType
{
    /** @var Formation */
    public $formation;

    /** @var Field */
    public $field;

    public function __construct(Formation $formation, Field $field)
    {
        $this->formation = $formation;

        $this->field = $field;
    }

    public abstract function render();
}