<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Fields\Picker;
use HeadlessLaravel\Formations\Fields\Select;
use HeadlessLaravel\Formations\Fields\Text;
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

    public function test_text_field_type()
    {
        $index = (new TextFormation())->getRenderedIndexFields();
        $create = (new TextFormation())->getRenderedCreateFields();
        $edit = (new TextFormation())->getRenderedEditFields();

        $this->assertEquals('Text', $index[0]->component);
        $this->assertEquals('Text', $create[0]->component);
        $this->assertEquals('Text', $edit[0]->component);
    }

    public function test_textarea_field_type()
    {
        $index = (new TextareaFormation())->getRenderedIndexFields();
        $create = (new TextareaFormation())->getRenderedCreateFields();
        $edit = (new TextareaFormation())->getRenderedEditFields();

        $this->assertCount(2, $index);
        $this->assertCount(2, $create);
        $this->assertCount(2, $edit);
        $this->assertEquals(10, $create[0]->props['rows']); // overridden in formation
        $this->assertEquals(5, $create[1]->props['rows']); // set in the field
        $this->assertEquals('Textarea', $index[0]->component);
        $this->assertEquals('Textarea', $create[0]->component);
        $this->assertEquals('Textarea', $edit[0]->component);
        $this->assertEquals('10', $index[0]->props['limit']);
    }

    public function test_select_field_type()
    {
        $index = (new SelectFormation())->getRenderedIndexFields();
        $create = (new SelectFormation())->getRenderedCreateFields();
        $edit = (new SelectFormation())->getRenderedEditFields();

        $this->assertCount(3, $index);
        $this->assertCount(3, $create);
        $this->assertCount(3, $edit);

        $this->assertEquals('Flat Status', $create[0]->key);
        $this->assertEquals('active', $create[0]->props['options'][0]);
        $this->assertEquals('draft', $create[0]->props['options'][1]);

        $this->assertEquals('Key Status', $create[1]->key);
        $this->assertEquals('Active', $create[1]->props['options']['active']);
        $this->assertEquals('Draft', $create[1]->props['options']['draft']);

        $this->assertEquals('Callable Status', $create[2]->key);
        $this->assertEquals('Active', $create[2]->props['options']['active']);
        $this->assertEquals('Draft', $create[2]->props['options']['draft']);
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
        $this->assertEquals('http://localhost/picker', $create[0]->props['url']);

        $this->assertCount(1, $edit);
        $this->assertEquals('required', $edit[0]->rules[0]);
        $this->assertEquals('exists:posts,id', $edit[0]->rules[1]);
    }
}

class TextFormation extends Formation
{
    public $model = Post::class;

    public function fields(): array
    {
        return [
            Text::make('Title'),
        ];
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

class SelectFormation extends Formation
{
    public $model = Post::class;

    public function fields(): array
    {
        return [
            Select::make('Flat Status')->options([
                'active',
                'draft',
            ]),

            Select::make('Key Status')->options([
                'active' => 'Active',
                'draft'  => 'Draft',
            ]),

            Select::make('Callable Status')->options(function () {
                return [
                    'active' => 'Active',
                    'draft'  => 'Draft',
                ];
            }),

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
