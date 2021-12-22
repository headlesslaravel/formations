<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class IncludesTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_include()
    {
        $this->markTestIncomplete();
        // PostFormation
        // Includes::make('author')
        $author = User::factory()->create();

        $this->get('posts?includes=author')
            ->assertJsonPath('data.0.author.id', $author->id)
            ->assertJsonPath('data.1.author.id', $author->id);

    }

    public function test_value_on_relationship_include()
    {
        $this->markTestIncomplete();
        // PostFormation
        // Includes::make('author_name', 'author.name')
        // example: post.author.name posts?includes=author_name
        $author = User::factory()->create();

        $this->get('posts?includes=author_name')
            ->assertJsonPath('data.0.author_name', $author->name)
            ->assertJsonPath('data.1.author_name', $author->name);
    }

    public function test_value_on_nested_relationship_include()
    {
        $this->markTestIncomplete();
        // PostFormation
        // Includes::make('author_post_titles', 'author.posts.title')
        // post.author.posts.title posts?includes=author_post_titles
        $author = User::factory()->create();

        $this->get('posts?includes=author_name')
            ->assertJsonPath('data.0.author_post_titles.0', $author->posts->pluck('title')[0])
            ->assertJsonPath('data.0.author_post_titles.1', $author->posts->pluck('title')[1]);
    }

    public function test_scope_on_relationship_include()
    {
        $this->markTestIncomplete();
        // PostFormation
        // Includes::make('comments')->scope('approved')
        // post.comments /posts?includes=comments
        // public function scopeApproved($query) {}
    }

    public function test_scope_with_required_parameter_on_relationship_include()
    {
        $this->markTestIncomplete();
        // PostFormation
        // Includes::make('comments')
            // ->scope('status', Request::input('status'))
            // ->rule('status', ['required'])
        // post.comments /posts?includes=comments&comment_status=approved
        // public function scopeStatus($query, $status) {}
        $this->get('posts?includes=comments&status=approved')
            ->assertJsonCount(1, 'data.0.comments')
            ->assertJsonPath('data.0.comments.0.status', 'approved');

        $this->get('posts?includes=comments&status=draft')
            ->assertJsonCount(1, 'data.0.comments')
            ->assertJsonPath('data.0.comments.0.status', 'draft');
    }

    public function test_scope_with_optional_parameter_on_relationship_include()
    {
        $this->markTestIncomplete();
        // PostFormation
        // Includes::make('comments')
            // ->scope('status', Request::input('status'))
            // ->rule('status', ['nullable'])
        // public function scopeStatus($query, $status = null) { if($status) { $query->thing(); }}
        // post with coment with status approved
        // post with coment with status draft
        $this->get('posts?includes=comments&status=')
            ->assertJsonCount(2, 'data.0.comments');
    }

    public function test_scope_with_required_parameter_on_relationship_include_with_validation_error()
    {
        $this->markTestIncomplete();
        // post.comments ?status=invalid
        // ->scope('status', Request::input('status'))
        // ->rule('status', ['required', 'in:approved,draft'])
        $this->get('posts?includes=comments&status=invalid')
            ->assertInvalid('status');
    }

    public function test_no_includes_in_response_when_not_authorized()
    {
        $this->markTestIncomplete();
         Gate::define('viewAuthor', function () {
            return true;
         });

         Gate::define('viewComments', function () {
            return false;
         });

        // Includes::make('author')->can('viewAuthor'),
        // Includes::make('coments')->can('viewComments'),
        // assert that comments are not in the response
        $author = User::factory()->create();

        $this->get('posts?includes=comments,author')
            ->assertJsonPath('data.0.comments', null)
            ->assertJsonPath('data.0.author.id', $author->id);
    }

    public function test_an_includes_with_an_api_resource_response()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-resources
        // Includes::make('author')->resource(AuthorResource::class),
        // /posts?includes=author
        // define a resource and add it to an includes
        // call a relationship
        // assert that only values within the resource are present
        $author = User::factory()->create();

        $this->get('posts?includes=author')
            ->assertJsonCount(2, 'data.0.author') // only 2 keys id, name
            ->assertJsonPath('data.0.author.id', $author->id)
            ->assertJsonPath('data.0.author.name', $author->name);
    }

    public function test_includes_count_aggregate()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-relationships#counting-related-models
        // Include::make('comments')->count()
        // create a post with two comments
        // assert response includes "comments_count": 2
    }

    public function test_includes_count_aggregate_with_scope()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-relationships#counting-related-models
        // Include::make('approved_comments', 'comments')->scope('approved')->count()
        // create a post with two comments, one approved one not
        // assert response includes count 1 not 2 for custom key "approved_comments"
    }

    public function test_includes_min_aggregate()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-relationships#other-aggregate-functions
        // Include::make('min_comment_id', 'comments')->min('id')
        // create a post with two comments
        // assert response includes first comment's id not the second
        // since its value will be lower than the later comment's id
    }

    public function test_includes_max_aggregate()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-relationships#other-aggregate-functions
        // Include::make('max_comment_id', 'comments')->max('id')
        // create a post with two comments
        // assert response includes second comment's id not the first
        // since its value will be higher than the later comment's id
    }

    public function test_includes_avg_aggregate()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-relationships#other-aggregate-functions
        // Include::make('max_comment_id', 'comments')->avg('rating')
        // create 2 posts with three comments each with different ratings
        // assert that the average of those ratings is outputted
    }

    public function test_includes_sum_aggregate()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-relationships#other-aggregate-functions
        // Include::make('rating_sum', 'comments')->sum('rating')
        // create 2 posts with three comments each with different ratings
        // assert that the sum of those ratings is outputted
    }

    public function test_includes_exists_aggregate()
    {
        $this->markTestIncomplete();
        // https://laravel.com/docs/8.x/eloquent-relationships#other-aggregate-functions
        // Include::make('is_liked', 'likes')->exists()
        // create 1 posts with 1 like
        // create 1 post with 0 likes
        $this->get('posts?include=is_liked')
            ->assertJsonPath('data.0.is_liked', true)
            ->assertJsonPath('data.1.is_liked', false);
    }

    public function test_exception_for_aggregate_on_non_collection_relationship()
    {
        $this->markTestIncomplete();
        // abort_if BelongsTo, HasOne, HasOneThrough,MorphOne
    }
}
