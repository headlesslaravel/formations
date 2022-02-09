<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Fields\Picker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_picker_field_type()
    {
        $field = Field::make('Brand')->as(Picker::class);

        $this->assertEquals('Brand', $field->key);
        $this->assertEquals(Picker::class, $field->type);
    }
}
