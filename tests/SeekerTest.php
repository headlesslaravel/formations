<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;

class SeekerTest extends TestCase
{
    public function test_seeker_global_search()
    {
        $post = Post::factory()->create(['title' => 'Hi post']);
        $author = User::factory()->create(['name' => 'Hi author']);

        $response = $this->get('search?term=hi');

        $response->assertJsonCount(2, 'data.0.data.0');
        $response->assertJsonPath('data.0.data.0.value', $post->id);
        $response->assertJsonPath('data.0.data.0.display', 'Hi post');

        $response->assertJsonCount(2, 'data.1.data.0');
        $response->assertJsonPath('data.1.data.0.value', $author->id);
        $response->assertJsonPath('data.1.data.0.display', 'Hi author');

        $response->assertJsonPath('data.0.meta.route', 'posts.show');
        $response->assertJsonPath('data.0.meta.group', 'posts');

        $response->assertJsonPath('data.1.meta.route', 'authors.show');
        $response->assertJsonPath('data.1.meta.group', 'authors');
    }
}
