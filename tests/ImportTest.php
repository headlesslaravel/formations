<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Exports\ImportTemplate;
use HeadlessLaravel\Formations\Mail\ImportErrorsMail;
use HeadlessLaravel\Formations\Mail\ImportSuccessMail;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Category;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Tag;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_exists()
    {
        $route = Route::getRoutes()->getByName('posts.imports.store');

        $this->assertEquals('imports/posts', $route->uri());
        $this->assertCount(1, $route->methods());
        $this->assertEquals('POST', $route->methods()[0]);
        $this->assertEquals('HeadlessLaravel\Formations\Http\Controllers\ImportController@store', $route->getAction()['uses']);
    }

    public function test_uploading_with_validation_errors()
    {
        Mail::fake();

        $csv = file_get_contents(__DIR__.'/Fixtures/Imports/posts-with-validation-errors.csv');

        $this->post('imports/posts', [
            'file' => UploadedFile::fake()->createWithContent('posts.csv', $csv),
        ])->assertOk();

        Mail::assertSent(function (ImportErrorsMail $mail) {
            $mail->build();
            $data = $mail->rawAttachments[0]['data'];
            $errors = 'The title must be at least 2 characters. The body must be at least 2 characters. The selected author is invalid. The selected category is invalid.';
            $firstRow = '"t","b","Brian","Tech","The title must be at least 2 characters. The body must be at least 2 characters. The selected author is invalid. The selected category is invalid."';

            return Str::startsWith($data, '"title","body","author","category","errors"')
                && Str::startsWith(Str::after($data, '"title","body","author","category","errors"'."\n"), $firstRow)
                && count($mail->errors[0]) == 5
                && $mail->errors[0]['errors'] == $errors
                && $mail->errors[0]['title'] == 't'
                && $mail->errors[0]['body'] == 'b'
                && $mail->errors[0]['author'] == 'Brian'
                && $mail->errors[0]['category'] == 'Tech';
        });

        Mail::assertNotSent(ImportSuccessMail::class);
    }

    public function test_uploading_with_relations_with_default_import()
    {
        Mail::fake();

        User::factory()->create(['name' => 'Susan']);
        User::factory()->create(['name' => 'Frank']);
        Category::factory()->create(['title' => 'Tech']);

        $csv = file_get_contents(__DIR__.'/Fixtures/Imports/posts.csv');

        $this->post('imports/posts', [
            'file' => UploadedFile::fake()->createWithContent('posts.csv', $csv),
        ])->assertOk();

        $posts = Post::all();

        $this->assertCount(2, $posts);

        $this->assertEquals('title 1', $posts[0]->title);
        $this->assertEquals('the body', $posts[0]->body);
        $this->assertEquals('Susan', $posts[0]->author->name);

        $this->assertEquals('title 2', $posts[1]->title);
        $this->assertEquals('the body', $posts[1]->body);
        $this->assertEquals('Frank', $posts[1]->author->name);
    }

    public function test_download_import_template()
    {
        Excel::fake();

        $this->get('imports/posts')->assertOk();

        Excel::assertDownloaded('posts.csv', function (ImportTemplate $export) {
            return $export->headings() == ['title', 'body', 'author', 'category', 'tags'];
        });
    }

    public function test_import_confirmation_email()
    {
        Mail::fake();

        User::factory()->create(['name' => 'Susan']);
        User::factory()->create(['name' => 'Frank']);
        Category::factory()->create(['title' => 'Tech']);

        $csv = file_get_contents(__DIR__.'/Fixtures/Imports/posts.csv');

        $this->post('imports/posts', [
            'file' => UploadedFile::fake()->createWithContent('posts.csv', $csv),
        ])->assertOk();

        Mail::assertSent(function (ImportSuccessMail $mail) {
            $mail->build();

            return $mail->subject === '2 successfully imported';
        });

        Mail::assertNotSent(ImportErrorsMail::class);
    }

    public function test_import_of_multiple_relationships_with_multiple_fields()
    {
        $csv = file_get_contents(__DIR__.'/Fixtures/Imports/categories.csv');

        $this->post('imports/categories', [
            'file' => UploadedFile::fake()->createWithContent('categories.csv', $csv),
        ])->assertOk();

        $categories = Category::all();
        $posts = Post::all();

        $this->assertCount(2, $categories);
        $this->assertCount(2, $posts);

        $this->assertEquals('category 1', $categories[0]->title);
        $this->assertEquals('category 2', $categories[1]->title);

        $this->assertEquals('blog post title1', $posts[0]->title);
        $this->assertEquals('blog post content1', $posts[0]->body);

        $this->assertEquals('blog post title2', $posts[1]->title);
        $this->assertEquals('blog post content2', $posts[1]->body);
    }

    public function test_import_of_multiple_relationships_with_single_field()
    {
        User::factory()->create(['name' => 'Susan']);
        User::factory()->create(['name' => 'Frank']);
        Category::factory()->create(['title' => 'Tech']);

        $csv = file_get_contents(__DIR__.'/Fixtures/Imports/posts_tags.csv');

        $this->post('imports/posts', [
            'file' => UploadedFile::fake()->createWithContent('posts.csv', $csv),
        ])->assertOk();

        $posts = Post::all();
        $tags = Tag::all();

        $this->assertCount(2, $posts);
        $this->assertCount(4, $tags);

        $this->assertEquals('title 1', $posts[0]->title);
        $this->assertEquals('laravel', $posts[0]->tags[0]->title);
        $this->assertEquals('vue', $posts[0]->tags[1]->title);

        $this->assertEquals('title 2', $posts[1]->title);
        $this->assertEquals('ruby', $posts[1]->tags[0]->title);
        $this->assertEquals('react', $posts[1]->tags[1]->title);
    }

    public function test_import_of_fail_of_invalid_data_for_multiple_relationships_with_single_field()
    {
        Mail::fake();

        User::factory()->create(['name' => 'Susan']);
        User::factory()->create(['name' => 'Frank']);
        Category::factory()->create(['title' => 'Tech']);

        $csv = file_get_contents(__DIR__.'/Fixtures/Imports/posts_tags_invalid.csv');

        $this->post('imports/posts', [
            'file' => UploadedFile::fake()->createWithContent('posts.csv', $csv),
        ])->assertOk();

        $posts = Post::all();
        $tags = Tag::all();

        $this->assertCount(0, $posts);
        $this->assertCount(0, $tags);

        Mail::assertSent(function (ImportErrorsMail $mail) {
            $mail->build();
            $data = $mail->rawAttachments[0]['data'];
            $firstRow = '"title 1","the body","Susan","Tech","[""n"",""vue""]","The tags.0 must be at least 2 characters."';
            $secondRow = '"title 2","the body","Frank","Tech","[""ruby"",""n""]","The tags.1 must be at least 2 characters."';

            return Str::startsWith($data, '"title","body","author","category","tags","errors"')
                && Str::startsWith(Str::after($data, '"title","body","author","category","tags","errors"'."\n"), $firstRow)
                && Str::startsWith(Str::after($data, $firstRow."\n"), $secondRow)
                && count($mail->errors) == 2
                && $mail->errors[0]['title'] == 'title 1'
                && $mail->errors[0]['errors'] == 'The tags.0 must be at least 2 characters.'
                && $mail->errors[1]['title'] == 'title 2'
                && $mail->errors[1]['errors'] == 'The tags.1 must be at least 2 characters.';
        });

        Mail::assertNotSent(ImportSuccessMail::class);
    }
}
