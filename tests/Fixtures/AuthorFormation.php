<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures;

use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;

class AuthorFormation extends Formation
{
    public $model = User::class;

    public $search = ['name'];

    public $display = 'name';

    public $foreignKey = 'author_id';
}
