<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\PostFormation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class FormationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('headless-formations.mode', 'api');
    }

    public function test_per_page()
    {
        $this->authUser();

        Post::factory(4)->create();

        $this->get('posts?per_page=2')
            ->assertJsonCount(2, 'posts')
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 4);
    }

    public function test_max_per_page()
    {
        $this->authUser();

        $this->get('posts?per_page=200')
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_redirect_page_if_exceeds_total_pages()
    {
        $this->authUser();

        $this->get('posts?page=2&search=hello')
            ->assertRedirect('/posts?page=1&search=hello&sort-desc=body');
        // sort comes from $defaults
    }

    public function test_defaults_on_request_class()
    {
        $this->authUser();
        // $defaults = ['sort-desc' => 'body'];

        Post::create(['body' => '1']);
        Post::create(['body' => '2']);
        Post::create(['body' => '3']);

        $this->get('posts')
            ->assertJsonPath('posts.0.body', '3')
            ->assertJsonPath('posts.1.body', '2')
            ->assertJsonPath('posts.2.body', '1');
    }

    public function test_calling_results_twice_is_cached()
    {
        $count = 0;

        DB::listen(function ($query) use (&$count) {
            $count++;
        });

        $request = (new PostFormation());
        $request->results();
        $request->results();

        $this->assertEquals(1, $count);
    }

    public function test_empty_sortable()
    {
        $request = new PostFormation();
        $request->defaults = [];
        $request->results();
        $this->assertTrue(true); // just appeasing test score
    }

    public function test_query_where_condition()
    {
        Post::factory()->create(['title' => 'Good']);
        Post::factory()->create(['title' => 'Bad']);

        $request = new PostFormation();
        $request->where('title', 'Good');
        $results = $request->results();

        $this->assertCount(1, $results);
        $this->assertEquals('Good', $results->first()->title);
    }
}
