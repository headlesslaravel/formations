<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PivotControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser();

        config()->set('formations.mode', 'api');
    }

    public function test_pivot_count()
    {
        Tag::factory()->create();

        $post = Post::factory()->create();

        $post->tags()->attach([
            Tag::factory()->create()->id,
            Tag::factory()->create()->id,
            Tag::factory()->create()->id,
        ]);

        $this->get("posts/$post->id/tags/count")
            ->assertOk()
            ->assertJsonPath('count', 3);
    }

    public function test_pivot_index()
    {
        Post::factory()->create()->tags()->attach(
            Tag::factory()->create()
        );

        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $post->tags()->attach($tag);

        $this->get("posts/$post->id/tags")
            ->assertOk()
            ->assertJsonCount(1, 'tags')
            ->assertJsonPath('tags.0.id', $tag->id);
    }

    public function test_pivot_search_index()
    {
        Post::factory()->create()->tags()->attach(
            Tag::factory()->create(['title' => 'Vue'])
        );

        $vue = Tag::factory()->create(['title' => 'Vue']);
        $react = Tag::factory()->create(['title' => 'React']);
        $post = Post::factory()->create();
        $post->tags()->attach([$vue->id, $react->id]);

        $this->get("posts/$post->id/tags?search=vue")
            ->assertOk()
            ->assertJsonCount(1, 'tags')
            ->assertJsonPath('tags.0.id', $vue->id);

        $this->get("posts/$post->id/tags?search=rea")
            ->assertOk()
            ->assertJsonCount(1, 'tags')
            ->assertJsonPath('tags.0.id', $react->id);
    }

    public function test_pivot_show()
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $post->tags()->attach($tag);

        $this->get("posts/$post->id/tags/{$tag->id}")
            ->assertOk()
            ->assertJsonPath('post.id', $post->id)
            ->assertJsonPath('post.title', $post->title)
            ->assertJsonPath('tag.id', $tag->id)
            ->assertJsonPath('tag.title', $tag->title)
            ->assertJsonCount(2, 'tag.pivot');
    }

    public function test_pivot_404_if_not_attached()
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();

        $this->get("posts/$post->id/tags/{$tag->id}")
            ->assertNotFound();
    }

    public function test_pivot_sync()
    {
        $post = Post::factory()->create();
        $one = Tag::factory()->create();
        $two = Tag::factory()->create();

        $response = $this->post("posts/$post->id/tags/sync", [
            'selected' => [$one->id, $two->id],
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'attached');
        $response->assertJsonCount(0, 'detached');
        $response->assertJsonCount(0, 'updated');
        $response->assertJsonPath('attached.0', $one->id);
        $response->assertJsonPath('attached.1', $two->id);
    }

    public function test_pivot_attach()
    {
        $post = Post::factory()->create();
        $one = Tag::factory()->create();
        $two = Tag::factory()->create();

        $response = $this->post("posts/$post->id/tags/attach", [
            'selected' => [$one->id, $two->id],
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'attached');
        $response->assertJsonPath('attached.0', $one->id);
        $response->assertJsonPath('attached.1', $two->id);
    }

    public function test_pivot_detach()
    {
        $post = Post::factory()->create();
        $one = Tag::factory()->create();
        $two = Tag::factory()->create();
        $post->tags()->attach([$one->id, $two->id]);
        $this->assertCount(2, $post->tags);

        $response = $this->delete("posts/$post->id/tags/detach", [
            'selected' => [$one->id, $two->id],
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'detached');
        $response->assertJsonPath('detached.0', $one->id);
        $response->assertJsonPath('detached.1', $two->id);
        $this->assertCount(0, $post->fresh()->tags);
    }

    public function test_pivot_toggle()
    {
        $post = Post::factory()->create();
        $one = Tag::factory()->create();
        $two = Tag::factory()->create();
        $three = Tag::factory()->create();
        $post->tags()->attach([$one->id, $two->id]);

        $response = $this->post("posts/$post->id/tags/toggle", [
            'selected' => [$one->id, $two->id, $three->id],
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'detached');
        $response->assertJsonCount(1, 'attached');
        $response->assertJsonPath('detached.0', $one->id);
        $response->assertJsonPath('detached.1', $two->id);
        $response->assertJsonPath('attached.0', $three->id);
        $this->assertCount(1, $post->fresh()->tags);
        $this->assertEquals($three->id, $post->fresh()->tags->first()->id);
    }
}
