<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Category;

class CategoryFormation extends Formation
{
    public $model = Category::class;

    public $display = 'title';

    public $search = [
        'title',
    ];

    public $sort = [
        'title',
    ];

    public $defaults = [
        'sort-desc' => 'title',
    ];

    public function import(): array
    {
        return [
            Field::make('title')->rules(['required', 'min:2']),
            Field::make('posts.*.title')->rules(['required', 'min:2']),
            Field::make('posts.*.body')->rules(['required', 'min:2']),
        ];
    }

    public function export(): array
    {
        return [
            Field::make('id'),
            Field::make('title'),
        ];
    }
}
