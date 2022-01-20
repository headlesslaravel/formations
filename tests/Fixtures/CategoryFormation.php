<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Category;

class CategoryFormation extends Formation
{
    public $search = ['title'];

    public $model = Category::class;

    public function import(): array
    {
        return [
            Field::make('title')->rules(['required', 'unique:categories,title'])
        ];
    }}
