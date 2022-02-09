<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures;

use HeadlessLaravel\Finders\Search;
use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Tag;

class TagFormation extends Formation
{
    public $model = Tag::class;

    public function search(): array
    {
        return [
            Search::make('title'),
        ];
    }
}
