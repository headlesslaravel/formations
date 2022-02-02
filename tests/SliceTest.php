<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Inertia\Response;

class SliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_formation_meta_includes_slice_links()
    {
        $this->authUser();

        config()->set('headless-formations.mode', 'inertia');
        Inertia::setRootView('testing::app');

        Post::factory()->create(['title' => 'Hello World']);

        $index = $this->getResourceController()
            ->response('index', Post::query()->paginate());

        $view = $index
            ->toResponse(request())
            ->getOriginalContent();

        $this->assertInstanceOf(Response::class, $index);
        $slices = $view->getData()['page']['props']['headless']['slices'];
        $this->assertCount(4, $slices);
        $this->assertEquals('active-posts', $slices[0]['link']);
        $this->assertEquals('inactive-posts', $slices[1]['link']);
        $this->assertEquals('my-posts', $slices[2]['link']);
        $this->assertEquals('active-posts-sort-body-desc', $slices[3]['link']);
    }

    public function test_formation_slice_using_current_auth_user_as_post_author()
    {
        config()->set('headless-formations.mode', 'api');
        $user = $this->authUser();

        $userOne = User::factory()->create();
        Post::factory(2)->create(['author_id' => $user->id]);
        Post::factory()->create(['author_id' => $userOne->id]);

        $this->get('/posts/my-posts')
            ->assertJsonCount(2, 'posts');
    }

    public function test_formation_slice_using_sort_desc_and_post_active_filter()
    {
        config()->set('headless-formations.mode', 'api');
        $this->authUser();

        Post::factory()->create(['active' => true, 'body' => 'body 1']);
        Post::factory()->create(['active' => true, 'body' => 'body 2']);
        Post::factory(2)->create(['active' => false]);

        $this->get('/posts/active-posts-sort-body-desc')
            ->assertJsonCount(2, 'posts')
            ->assertJsonPath('posts.0.body', 'body 2')
            ->assertJsonPath('posts.1.body', 'body 1');
    }

    public function test_formation_slice_using_sort_desc_and_post_active_filter_with_sort_in_url()
    {
        config()->set('headless-formations.mode', 'api');
        $this->authUser();

        Post::factory()->create(['active' => true, 'title' => 'title 1', 'body' => 'body 1']);
        Post::factory()->create(['active' => true, 'title' => 'title 2', 'body' => 'body 2']);
        Post::factory(2)->create(['active' => false]);

        $this->get('/posts/active-posts-sort-body-desc?sort=title')
            ->assertJsonCount(2, 'posts')
            ->assertJsonPath('posts.0.title', 'title 1')
            ->assertJsonPath('posts.1.title', 'title 2');
    }
}
