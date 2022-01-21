<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Exports\ImportTemplate;
use HeadlessLaravel\Formations\Mail\ImportErrorsMail;
use HeadlessLaravel\Formations\Mail\ImportSuccessMail;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Category;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportTest extends TestCase
{
    protected $useMysql = true;

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
            return $export->headings() == ['title', 'body', 'author', 'category'];
        });
    }

    public function test_uploading_with_unique_validation_errors()
    {
        Mail::fake();

        $csv = file_get_contents(__DIR__.'/Fixtures/Imports/with-unique-rule-and-duplicates.csv');

        $this->post('imports/categories', [
            'file' => UploadedFile::fake()->createWithContent('categories.csv', $csv),
        ])->assertOk();

        Mail::assertSent(function (ImportErrorsMail $mail) {
            $mail->build();
            $data = $mail->rawAttachments[0]['data'];
            $errors = 'The title has already been taken.';
            $firstRow = '"tech","The title has already been taken."';

            return Str::startsWith($data, '"title","errors"')
                && Str::startsWith(Str::after($data, '"title","errors"'."\n"), $firstRow)
                && count($mail->errors[0]) == 2
                && $mail->errors[0]['errors'] == $errors
                && $mail->errors[0]['title'] == 'tech';
        });

        Mail::assertNotSent(ImportSuccessMail::class);

        $this->assertCount(1, Category::all());
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
}
