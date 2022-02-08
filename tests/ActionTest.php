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

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => 'all', 'fields' => ['status' => 'active']]
        )->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('id', $data);

        $batchId = $data['id'];

        Queue::assertPushed(SetStatus::class, 2);
        Queue::assertPushed(SetStatus::class, function (SetStatus $job) use ($batchId) {
            return $job->batchId === $batchId;
        });
    }

    public function test_actions_for_single_models()
    {
        Queue::fake();

        $user = $this->authUser();

        $post = Post::factory()->create(['author_id' => $user->id]);
        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => $post->id, 'fields' => ['status' => 'active']]
        )->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('id', $data);

        $batchId = $data['id'];

        Queue::assertPushed(SetStatus::class, 1);
        Queue::assertPushed(SetStatus::class, function (SetStatus $job) use ($batchId) {
            return $job->batchId === $batchId;
        });
    }

    public function test_actions_for_selected_models()
    {
        Queue::fake();

        $user = $this->authUser();

        $postOne = Post::factory()->create(['author_id' => $user->id]);
        $postTwo = Post::factory()->create(['author_id' => $user->id]);
        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => [$postOne->id, $postTwo->id], 'fields' => ['status' => 'active']]
        )->assertOk();
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
        Post::factory(2)->create(['author_id' => $author->id]);

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => 'all', 'fields' => ['status' => 'active'], 'author' => 1, 'sort-desc' => 'title']
        )->assertOk();
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

        $this->post(
            'actions/posts/set-status',
            ['selected' => 'all', 'fields' => ['status' => 'invalid-status-type']]
        )->assertInvalid(['fields.status']);
    }

    public function test_actions_for_all_models_without_fake()
    {
        $this->app['config']->set('queue.default', 'database');
        $user = $this->authUser();

        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => 'all', 'fields' => ['status' => 'active']]
        )->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('id', $data);

        $batchId = $data['id'];

        $batchProgressResponse = $this->get("actions/posts/set-status/$batchId")->assertOk();
        $batchProgressData = $batchProgressResponse->json();

        $this->assertArrayHasKey('status', $batchProgressData);
        $this->assertArrayHasKey('total', $batchProgressData);
        $this->assertArrayHasKey('processed', $batchProgressData);

        $this->assertEquals('in-progress', $batchProgressData['status']);
        $this->assertEquals(2, $batchProgressData['total']);
        $this->assertEquals(0, $batchProgressData['processed']);
    }
}
