<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\AuthorFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use HeadlessLaravel\Formations\Tests\Fixtures\PostFormation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class SeekerTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertJsonPath('data.0.meta.resource', 'posts');

        $response->assertJsonPath('data.1.meta.route', 'authors.show');
        $response->assertJsonPath('data.1.meta.resource', 'authors');
    }

    public function test_seeker_global_search_with_prefix()
    {
        $post = Post::factory()->create(['title' => 'Hi post']);
        $author = User::factory()->create(['name' => 'Hi author']);

        Route::prefix('admin')->group(function () {
            Route::formation('posts', PostFormation::class);
            Route::formation('authors', AuthorFormation::class);

            Route::seeker('search', [
                PostFormation::class,
                AuthorFormation::class,
            ]);
        });

        $response = $this->get('admin/search?term=hi');

        $response->assertJsonCount(2, 'data.0.data.0');
        $response->assertJsonPath('data.0.data.0.value', $post->id);
        $response->assertJsonPath('data.0.data.0.display', 'Hi post');

        $response->assertJsonCount(2, 'data.1.data.0');
        $response->assertJsonPath('data.1.data.0.value', $author->id);
        $response->assertJsonPath('data.1.data.0.display', 'Hi author');

        $response->assertJsonPath('data.0.meta.route', 'admin.posts.show');
        $response->assertJsonPath('data.0.meta.resource', 'posts');

        $response->assertJsonPath('data.1.meta.route', 'admin.authors.show');
        $response->assertJsonPath('data.1.meta.resource', 'authors');
    }
}
