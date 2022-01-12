<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures;

use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Tag;

class TagFormation extends Formation
{
    public $search = ['title'];

    public $model = Tag::class;
}
