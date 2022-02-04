<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures;

use HeadlessLaravel\Finders\Search;
use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;

class AuthorFormation extends Formation
{
    public $model = User::class;

    public $display = 'name';

    public $foreignKey = 'author_id';

    public function search(): array
    {
        return [
            Search::make('name'),
        ];
    }
}
