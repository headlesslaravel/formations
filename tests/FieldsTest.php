<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Fields\Picker;
use HeadlessLaravel\Formations\Fields\Textarea;
use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use Illuminate\Support\Facades\Route;

class FieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/picker', function () {})->name('pickers.index');
    }

    public function test_textarea_field_type()
    {
        $index = (new TextareaFormation())->getRenderedIndexFields();
        $create = (new TextareaFormation())->getRenderedCreateFields();
        $edit = (new TextareaFormation())->getRenderedEditFields();

        $this->assertCount(2, $index);
        $this->assertCount(2, $create);
        $this->assertEquals(10, $create[0]->props['rows']);
        $this->assertEquals(5, $create[1]->props['rows']);
        $this->assertCount(2, $edit);
    }

    public function test_picker_field_type()
    {
        $index = (new PickerFormation())->getRenderedIndexFields();
        $create = (new PickerFormation())->getRenderedCreateFields();
        $edit = (new PickerFormation())->getRenderedEditFields();

        $this->assertCount(1, $index);
        $this->assertNull($index[0]->rules);

        $this->assertCount(1, $create);
        $this->assertEquals('required', $create[0]->rules[0]);
        $this->assertEquals('exists:posts,id', $create[0]->rules[1]);

        $this->assertCount(1, $edit);
        $this->assertEquals('required', $edit[0]->rules[0]);
        $this->assertEquals('exists:posts,id', $edit[0]->rules[1]);
    }
}

class TextareaFormation extends Formation
{
    public $model = Post::class;

    public function fields(): array
    {
        return [
            Textarea::make('Bio')->rows(10),
            Textarea::make('Description'),
        ];
    }
}

class PickerFormation extends Formation
{
    public $model = Post::class;

    public function fields(): array
    {
        return [
            Picker::make('Author'),
        ];
    }
}
