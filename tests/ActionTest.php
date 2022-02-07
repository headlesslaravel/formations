<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Jobs\SetStatus;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;

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
        Bus::fake();

        $user = $this->authUser();

        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => 'all', 'fields' => ['status' => 'active']]
        )->assertOk();
        $this->assertArrayHasKey('id', $response->json());

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatchFake $batch) {
            $countCheck = $batch->jobs->count() === 2;
            $instanceCheck = $batch->jobs[0] instanceof SetStatus && $batch->jobs[1] instanceof SetStatus;

            return $countCheck && $instanceCheck;
        });
    }

    public function test_actions_for_single_models()
    {
        Bus::fake();

        $user = $this->authUser();

        $post = Post::factory()->create(['author_id' => $user->id]);
        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => $post->id, 'fields' => ['status' => 'active']]
        )->assertOk();
        $this->assertArrayHasKey('id', $response->json());

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatchFake $batch) use ($post) {
            $countCheck = $batch->jobs->count() === 1;
            $instanceCheck = $batch->jobs[0] instanceof SetStatus;
            $postModelCheck = $batch->jobs[0]->post->id === $post->id;

            return $countCheck && $instanceCheck && $postModelCheck;
        });
    }

    public function test_actions_for_selected_models()
    {
        Bus::fake();

        $user = $this->authUser();

        $postOne = Post::factory()->create(['author_id' => $user->id]);
        $postTwo = Post::factory()->create(['author_id' => $user->id]);
        Post::factory(2)->create(['author_id' => $user->id]);

        $response = $this->post(
            'actions/posts/set-status',
            ['selected' => [$postOne->id, $postTwo->id], 'fields' => ['status' => 'active']]
        )->assertOk();
        $this->assertArrayHasKey('id', $response->json());

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatchFake $batch) use ($postOne, $postTwo) {
            $countCheck = $batch->jobs->count() === 2;
            $instanceCheck = $batch->jobs[0] instanceof SetStatus && $batch->jobs[1] instanceof SetStatus;
            $postOneModelCheck = $batch->jobs[0]->post->id === $postOne->id;
            $postTwoModelCheck = $batch->jobs[1]->post->id === $postTwo->id;

            return $countCheck && $instanceCheck && $postOneModelCheck && $postTwoModelCheck;
        });
    }

    public function test_actions_invalid_fields()
    {
        Bus::fake();

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
        $this->assertArrayHasKey('worked', $batchProgressData);

        $this->assertEquals('in-progress', $batchProgressData['status']);
        $this->assertEquals(2, $batchProgressData['total']);
        $this->assertEquals(0, $batchProgressData['worked']);
    }
}
