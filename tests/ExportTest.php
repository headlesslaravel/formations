<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Exports\Export;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_exists()
    {
        $route = Route::getRoutes()->getByName('posts.exports.create');

        $this->assertEquals('exports/posts', $route->uri());
        $this->assertCount(2, $route->methods());
        $this->assertEquals('GET', $route->methods()[0]);
        $this->assertEquals('HeadlessLaravel\Formations\Http\Controllers\ExportController@create', $route->getAction()['uses']);
    }

    public function test_exporting_with_relations_with_default_export_and_relations()
    {
        $this->travelTo(Carbon::create('2022', '01', '29', '8', '30', '02', 'America/New_York'));

        Excel::fake();

        $this->authUser();

        $user = User::factory()->create(['name' => 'John Doe']);
        Post::factory()->create(['title' => 'Post1', 'body' => 'Post Body 1', 'author_id' => $user->id]);
        Post::factory()->create(['title' => 'Post2', 'body' => 'Post Body 2', 'author_id' => $user->id]);

        $this->get('exports/posts')->assertOk();

        Excel::assertDownloaded('posts_2022-01-29_01:30:02.xlsx', function (Export $export) {
            // Assert that the correct export is downloaded.
            $data = $export->collection()->toArray();
            $count = count($data) == 2;
            $title = $data[0]['title'] === 'Post2' && $data[1]['title'] === 'Post1'; // default sort body desc
            $authorName = $data[0]['author_name'] === 'John Doe' && $data[1]['author_name'] === 'John Doe';

            return $count && $title && $authorName;
        });
    }

    public function test_exporting_with_relations_with_export_columns()
    {
        $this->travelTo(Carbon::create('2022', '01', '29', '8', '30', '02', 'America/New_York'));

        Excel::fake();

        $this->authUser();

        $user = User::factory()->create(['name' => 'John Doe']);
        Post::factory()->create(['title' => 'Post1', 'author_id' => $user->id]);
        Post::factory()->create(['title' => 'Post2', 'author_id' => $user->id]);

        $this->get('exports/posts?columns=id,title')->assertOk();

        Excel::assertDownloaded('posts_2022-01-29_01:30:02.xlsx', function (Export $export) {
            // Assert that the correct export is downloaded.
            $data = $export->collection()->toArray();
            $count = count($data) == 2;
            $resultObjectFieldsCount = count($data[0]) == 2;

            return $count && $resultObjectFieldsCount;
        });
    }

    public function test_exporting_with_invalid_export_columns()
    {
        $this->authUser();

        $this->get('exports/posts?columns=title,invalid_1,invalid_2')
            ->assertInvalid(['columns' => 'Invalid columns: invalid_1, invalid_2']);
    }

    public function test_exporting_with_filters()
    {
        $this->travelTo(Carbon::create('2022', '01', '29', '8', '30', '02', 'America/New_York'));

        Excel::fake();

        $this->authUser();

        $userOne = User::factory()->create(['name' => 'John Doe']);
        $userTwo = User::factory()->create(['name' => 'John Two']);
        Post::factory()->create(['title' => 'Post1', 'body' => 'Post Body 1', 'author_id' => $userOne->id]);
        Post::factory()->create(['title' => 'Post2', 'body' => 'Post Body 2', 'author_id' => $userOne->id]);
        Post::factory()->create(['title' => 'Post3', 'body' => 'Post Body 3', 'author_id' => $userTwo->id]);

        $this->get('exports/posts?author_id='.$userOne->id)->assertOk();

        Excel::assertDownloaded('posts_2022-01-29_01:30:02.xlsx', function (Export $export) {
            // Assert that the correct export is downloaded.
            $data = $export->collection()->toArray();
            $count = count($data) == 2;
            $title = $data[0]['title'] === 'Post2' && $data[1]['title'] === 'Post1'; // default sort body desc
            $authorName = $data[0]['author_name'] === 'John Doe' && $data[1]['author_name'] === 'John Doe';

            return $count && $title && $authorName;
        });
    }
}
