<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Imports\ExportImportTemplate;
use HeadlessLaravel\Formations\Mail\ImportErrorsMail;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
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
            $errors = 'The title must be at least 2 characters. The body must be at least 2 characters. The selected author is invalid.';
            $firstRow = '"t","b","Brian","The title must be at least 2 characters. The body must be at least 2 characters. The selected author is invalid."';

            return Str::startsWith($data, '"title","body","author","errors"')
                && Str::startsWith(Str::after($data, '"title","body","author","errors"'."\n"), $firstRow)
                && count($mail->errors[0]) == 4
                && $mail->errors[0]['errors'] == $errors
                && $mail->errors[0]['title'] == 't'
                && $mail->errors[0]['body'] == 'b'
                && $mail->errors[0]['author'] == 'Brian';
        });
    }

    public function test_uploading_with_relations_with_default_import()
    {
        User::factory()->create(['name' => 'Susan']);
        User::factory()->create(['name' => 'Frank']);

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

    public function test_download_import_export_template()
    {
        Excel::fake();

        $this->get('import-template/posts')->assertOk();

        Excel::assertDownloaded('posts.csv', function(ExportImportTemplate $export) {
            // Assert that the correct export is downloaded.
            return $export->headings() == ['title', 'body', 'author'];
        });
    }
}
