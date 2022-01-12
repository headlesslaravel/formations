<?php

namespace HeadlessLaravel\Formations\Tests;

use Illuminate\Support\Facades\File;

class MakeCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        app()->setBasePath(__DIR__.'/App');
        mkdir(__DIR__.'/App');
        file_put_contents(__DIR__.'/App/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'Testing\\' => realpath(base_path()),
                    'App\\' => 'app/',
                ],
            ],
        ]));
    }

    public function tearDown(): void
    {
        File::deleteDirectory(__DIR__.'/App');

        parent::tearDown();
    }

    public function test_make_command()
    {
        $this->artisan('make:formation ArticleFormation');
        $this->assertTrue(file_exists(base_path('app/Http/Formations/ArticleFormation.php')));
    }

    public function test_make_command_has_model_name_with_model_option()
    {
        mkdir(base_path('app'));
        mkdir(base_path('app/Models'));
        file_put_contents(base_path('app/Models/Article.php'), '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Article extends Model { }');
        $this->artisan('make:formation', ['name' => 'ArticleFormation', '--model' => 'App\Models\Article']);
        $this->assertTrue(file_exists(base_path('app/Http/Formations/ArticleFormation.php')));
        $this->assertStringContainsString(
            'public $model = \\App\\Models\\Article::class;',
            file_get_contents(base_path('app/Http/Formations/ArticleFormation.php'))
        );
    }

    public function test_make_command_has_model_name_with_guess_model()
    {
        mkdir(base_path('app'));
        mkdir(base_path('app/Models'));
        file_put_contents(base_path('app/Models/Article.php'), '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Article extends Model { }');
        $this->artisan('make:formation ArticleFormation');
        $this->assertTrue(file_exists(base_path('app/Http/Formations/ArticleFormation.php')));
        $this->assertStringContainsString(
            'public $model = \\App\\Models\\Article::class;',
            file_get_contents(base_path('app/Http/Formations/ArticleFormation.php'))
        );
    }

    public function test_make_command_custom_stub()
    {
        mkdir(base_path('stubs'));
        file_put_contents(base_path('stubs/formation.stub'), 'hello');
        $this->artisan('make:formation ArticleFormation');
        $this->assertTrue(file_exists(base_path('app/Http/Formations/ArticleFormation.php')));
        $this->assertEquals('hello', file_get_contents(base_path('app/Http/Formations/ArticleFormation.php')));
    }
}
