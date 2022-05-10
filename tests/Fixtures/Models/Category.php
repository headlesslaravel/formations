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

    public function imported($row)
    {
        if (isset($row['posts'])) {
            foreach ($row['posts'] as $post) {
                $post['category_id'] = $this->id;
                Post::create($post);
            }
        }
    }
}
