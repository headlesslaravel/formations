<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Jobs\SetStatus;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class ActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('headless-formations.mode', 'api');
    }

    public function test_actions_for_all_models()
    {
        Queue::fake();

        $user = $this->authUser();

        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post('actions/posts/set-status', [
            'selected' => 'all',
            'fields' => ['status' => 'active']
        ])->assertOk();

        $data = $response->json();

        $this->assertArrayHasKey('id', $data);

        Queue::assertPushed(SetStatus::class, 2);
        Queue::assertPushed(SetStatus::class, function (SetStatus $job) use ($data) {
            return $job->batchId === $data['id'];
        });
    }

    public function test_actions_for_single_models()
    {
        Queue::fake();

        $user = $this->authUser();

        $post = Post::factory()->create(['author_id' => $user->id]);

        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post('actions/posts/set-status', [
            'selected' => $post->id,
            'fields' => ['status' => 'active']
        ])->assertOk();

        $data = $response->json();

        $this->assertArrayHasKey('id', $data);

        Queue::assertPushed(SetStatus::class, 1);
        Queue::assertPushed(SetStatus::class, function (SetStatus $job) use ($data) {
            return $job->batchId === $data['id'];
        });
    }

    public function test_actions_for_selected_models()
    {
        Queue::fake();

        $user = $this->authUser();

        $postOne = Post::factory()->create(['author_id' => $user->id]);
        $postTwo = Post::factory()->create(['author_id' => $user->id]);
        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post('actions/posts/set-status', [
            'selected' => [$postOne->id, $postTwo->id],
            'fields' => ['status' => 'active']
        ])->assertOk();

        $data = $response->json();
        $this->assertArrayHasKey('id', $data);

        $postIds = [$postOne->id, $postTwo->id];
        Queue::assertPushed(SetStatus::class, 2);
        Queue::assertPushed(SetStatus::class, function (SetStatus $job) use ($postIds) {
            return in_array($job->post->id, $postIds);
        });
    }

    public function test_actions_for_filters()
    {
        Queue::fake();

        $user = $this->authUser();
        $author = User::factory()->create(['name' => 'Hi author']);

        $postOne = Post::factory()->create(['author_id' => $user->id, 'title' => 'Title 1']);
        $postTwo = Post::factory()->create(['author_id' => $user->id, 'title' => 'Title 2']);
        Post::factory()->create(['author_id' => $user->id, 'title' => 'Random', 'body' => 'random body']);
        Post::factory(2)->create(['author_id' => $author->id]);

        $response = $this->post('actions/posts/set-status', [
            'selected' => 'all',
            'fields'   => ['status' => 'active'],
            'query'    => ['author' => $user->id, 'sort-desc' => 'title', 'search' => 'Title'],
        ])->assertOk();

        $data = $response->json();
        $this->assertArrayHasKey('id', $data);

        $postIds = [$postOne->id, $postTwo->id];
        Queue::assertPushed(SetStatus::class, 2);
        Queue::assertPushed(SetStatus::class, function (SetStatus $job) use ($postIds) {
            return in_array($job->post->id, $postIds);
        });

        /** @var SetStatus[] $jobs */
        $jobs = Queue::pushedJobs()[SetStatus::class];
        $this->assertCount(2, $jobs);
        $this->assertEquals($jobs[0]['job']->post->id, $postTwo->id);
        $this->assertEquals($jobs[1]['job']->post->id, $postOne->id);
    }

    public function test_actions_invalid_fields()
    {
        $this->authUser();

        $this->post('actions/posts/set-status', [
            'selected' => 'all',
            'fields' => ['status' => 'invalid-status-type']
        ])->assertInvalid(['fields.status']);
    }

    public function test_actions_in_progress_status()
    {
        // sync will process jobs.. lets us hold off so we can see status
        $this->app['config']->set('queue.default', 'database');

        $user = $this->authUser();

        Post::factory(2)->create([
            'author_id' => $user->id,
            'status' => 'draft',
        ]);

        $batchId = $this->post('actions/posts/set-status', [
            'selected' => 'all',
            'fields' => ['status' => 'active']
        ])->json('id');

        $data = $this->get("actions/posts/set-status/$batchId")
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('processed', $data);

        $this->assertEquals('in-progress', $data['status']);
        $this->assertEquals(2, $data['total']);
        $this->assertEquals(0, $data['processed']);

        $this->assertEquals(2, Post::count());
        $this->assertEquals(2, Post::where('status', 'draft')->count());
    }

    public function test_actions_completed_status()
    {
        $user = $this->authUser();

        Post::factory(2)->create([
            'author_id' => $user->id,
            'status' => 'draft',
        ]);

        $batchId = $this->post('actions/posts/set-status', [
            'selected' => 'all',
            'fields' => ['status' => 'active']
        ])->json('id');

        $data = $this->get("actions/posts/set-status/$batchId")
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('processed', $data);

        $this->assertEquals('complete', $data['status']);
        $this->assertEquals(2, $data['total']);
        $this->assertEquals(2, $data['processed']);

        $this->assertEquals(2, Post::count());
        $this->assertEquals(2, Post::where('status', 'active')->count());
        $this->assertEquals(0, Post::where('status', 'draft')->count());
    }

    public function test_actions_progress_not_found_with_random_id()
    {
        $this->authUser();

        $this->get('actions/12345')->assertNotFound();
    }

    public function test_actions_policy_return_false()
    {
        $this->authUser();
        $this->updateAbilities([]); // remove setStatus

        $this->post('actions/posts/set-status', [
            'selected' => 'all',
            'fields' => ['status' => 'active']
        ])->assertForbidden();
    }
}
