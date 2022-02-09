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

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('headless-formations.mode', 'api');
    }

    public function test_formation_meta_includes_slice_links()
    {
        config()->set('headless-formations.mode', 'inertia');

        $this->authUser();

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
        $this->assertEquals('active-posts-sort-title-desc', $slices[3]['link']);
    }

    public function test_formation_slice_using_current_auth_user_as_post_author()
    {
        $user = $this->authUser();

        $userOne = User::factory()->create();
        Post::factory(2)->create(['author_id' => $user->id]);
        Post::factory()->create(['author_id' => $userOne->id]);

        $this->get('/posts/my-posts')
            ->assertJsonCount(2, 'posts');
    }

    public function test_formation_slice_using_sort_desc_and_post_active_filter()
    {
        $this->authUser();

        Post::factory()->create(['active' => true, 'title' => 'title 1']);
        Post::factory()->create(['active' => true, 'title' => 'title 2']);
        Post::factory(2)->create(['active' => false]);

        $this->get('posts/active-posts-sort-title-desc')
            ->assertJsonCount(2, 'posts')
            ->assertJsonPath('posts.0.title', 'title 2')
            ->assertJsonPath('posts.1.title', 'title 1');
    }

    public function test_overriding_formation_slice_using_sort_desc_and_post_active_filter_with_sort_in_url()
    {
        $this->authUser();

        Post::factory()->create(['active' => true, 'title' => 'title 3', 'body' => 'body 2']);
        Post::factory()->create(['active' => true, 'title' => 'title 1', 'body' => 'body 1']);
        Post::factory()->create(['active' => true, 'title' => 'title 2', 'body' => 'body 2']);

        Post::factory(2)->create(['active' => false]);

        $this->get('/posts/active-posts-sort-title-desc?sort=title')
            ->assertJsonCount(3, 'posts')
            ->assertJsonPath('posts.0.title', 'title 1')
            ->assertJsonPath('posts.1.title', 'title 2')
            ->assertJsonPath('posts.2.title', 'title 3');
    }
}
