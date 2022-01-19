<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures\Models;

use HeadlessLaravel\Formations\Tests\Fixtures\Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $guarded = [];

    public static function factory()
    {
        return CategoryFactory::new();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
