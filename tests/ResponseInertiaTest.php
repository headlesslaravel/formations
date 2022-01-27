<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ResponseInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('headless-formations.mode', 'inertia');

        Inertia::setRootView('testing::app');
    }

    public function test_index_inertia_responses()
    {
        Post::factory()->create(['title' => 'Hello World']);

        $index = $this->getResourceController()
            ->response('index', Post::query()->paginate());

        $view = $index
            ->toResponse(request())
            ->getOriginalContent();

        $this->assertInstanceOf(Response::class, $index);
        $this->assertEquals('testing::app', $view->name());
        $this->assertArrayHasKey('page', $view->getData());
        $this->assertEquals('Resources/Index', $view->getData()['page']['component']);
        $this->assertEquals('Hello World', $view->getData()['page']['props']['posts']->data[0]->title);
    }

    public function test_create_inertia_responses()
    {
        $create = $this
            ->getResourceController()
            ->response('create');

        $view = $create
            ->toResponse(request())
            ->getOriginalContent();

        $this->assertInstanceOf(Response::class, $create);
        $this->assertEquals('testing::app', $view->name());
        $this->assertArrayHasKey('page', $view->getData());
        $this->assertEquals('Resources/Create', $view->getData()['page']['component']);
        $this->assertEquals('populated from extra method', $view->getData()['page']['props']['extra']);
    }

    public function test_show_inertia_responses()
    {
        $post = Post::factory()->create(['title' => 'Hello World']);

        $show = $this
            ->getResourceController()
            ->response('show', $post);

        $view = $show
            ->toResponse(request())
            ->getOriginalContent();

        $this->assertInstanceOf(Response::class, $show);
        $this->assertEquals('testing::app', $view->name());
        $this->assertArrayHasKey('page', $view->getData());
        $this->assertEquals('Resources/Show', $view->getData()['page']['component']);
        // TODO: should the following be ->data?
//        dd($view->getData()['page']['props']['post']);
        $this->assertEquals('Hello World', $view->getData()['page']['props']['post']->title);
    }

    public function test_store_inertia_responses()
    {
        $post = Post::factory()->create();

        $store = $this
            ->getResourceController()
            ->response('store', $post);

        $this->assertInstanceOf(RedirectResponse::class, $store);
        $this->assertEquals(url(route('posts.show', $post)), $store->getTargetUrl());
    }

    public function test_edit_inertia_responses()
    {
        $post = Post::factory()->create(['title' => 'Hello World']);

        $edit = $this
            ->getResourceController()
            ->response('edit', $post);

        $view = $edit
            ->toResponse(request())
            ->getOriginalContent();

        $this->assertInstanceOf(Response::class, $edit);
        $this->assertEquals('testing::app', $view->name());
        $this->assertArrayHasKey('page', $view->getData());
        $this->assertEquals('Resources/Edit', $view->getData()['page']['component']);
        $this->assertEquals($post->id, $view->getData()['page']['props']['id']);
        $this->assertEquals('populated from override method', $view->getData()['page']['props']['override']);
    }

    public function test_update_inertia_responses()
    {
        $post = Post::factory()->create();

        $update = $this
            ->getResourceController()
            ->response('update', $post);

        $this->assertInstanceOf(RedirectResponse::class, $update);
        $this->assertEquals(url(route('posts.show', $post)), $update->getTargetUrl());
    }

    public function test_destroy_inertia_responses()
    {
        $post = Post::factory()->create();

        $destroy = $this
            ->getResourceController()
            ->response('destroy', $post);

        $this->assertInstanceOf(RedirectResponse::class, $destroy);
        $this->assertEquals(url(route('posts.index')), $destroy->getTargetUrl());
    }

    public function test_restore_inertia_responses()
    {
        $post = Post::factory()->create();

        $restore = $this
            ->getResourceController()
            ->response('restore', $post);

        $this->assertInstanceOf(RedirectResponse::class, $restore);
        $this->assertEquals(url(route('posts.show', $post)), $restore->getTargetUrl());
    }

    public function test_force_delete_inertia_responses()
    {
        $post = Post::factory()->create();

        $forceDelete = $this
            ->getResourceController()
            ->response('force-delete', $post);

        $this->assertInstanceOf(RedirectResponse::class, $forceDelete);
        $this->assertEquals(url(route('posts.index')), $forceDelete->getTargetUrl());
    }
}
