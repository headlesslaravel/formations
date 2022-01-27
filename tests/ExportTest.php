<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Exports\Export;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Excel::fake();

        $this->authUser();

        $user = User::factory()->create(['name' => 'John Doe']);
        Post::factory()->create(['title' => 'Post1', 'author_id' => $user->id]);
        Post::factory()->create(['title' => 'Post2', 'author_id' => $user->id]);

        $response = $this->get('exports/posts');
        $response->assertOk();

        Excel::assertDownloaded('posts.xlsx', function (Export $export) {
            // Assert that the correct export is downloaded.
            $data = $export->collection()->toArray();
            $count = count($data) == 2;
            $title = $data[0]['title'] === 'Post1' && $data[1]['title'] === 'Post2';
            $authorName = $data[0]['author_name'] === 'John Doe' && $data[1]['author_name'] === 'John Doe';

            return $count && $title && $authorName;
        });
    }

    public function test_exporting_with_relations_with_export_columns()
    {
        Excel::fake();

        $this->authUser();

        $user = User::factory()->create(['name' => 'John Doe']);
        Post::factory()->create(['title' => 'Post1', 'author_id' => $user->id]);
        Post::factory()->create(['title' => 'Post2', 'author_id' => $user->id]);

        $response = $this->get('exports/posts?columns=id,title');
        $response->assertOk();

        Excel::assertDownloaded('posts.xlsx', function (Export $export) {
            // Assert that the correct export is downloaded.
            $data = $export->collection()->toArray();
            $count = count($data) == 2;
            $resultObjectFieldsCount = count($data[0]) == 2;

            return $count && $resultObjectFieldsCount;
        });
    }
}
