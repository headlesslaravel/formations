<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\FormationProvider;
use HeadlessLaravel\Formations\Http\Controllers\Controller;
use HeadlessLaravel\Formations\Manager;
use HeadlessLaravel\Formations\Tests\Fixtures\PostFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\TestProvider;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\ExcelServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    protected $useMysql = false;

    protected function getPackageProviders($app)
    {
        return [
            ExcelServiceProvider::class,
            RayServiceProvider::class,
            FormationProvider::class,
            TestProvider::class,
        ];
    }

    public function useMysql()
    {
        $this->useMysql = true;
    }

    public function getEnvironmentSetUp($app)
    {
        if (!$this->useMysql) {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite', [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]);
        }

        include_once __DIR__.'/Fixtures/Database/migrations/create_users_table.php.stub';
        include_once __DIR__.'/Fixtures/Database/migrations/create_posts_table.php.stub';
        include_once __DIR__.'/Fixtures/Database/migrations/create_likes_table.php.stub';
        include_once __DIR__.'/Fixtures/Database/migrations/create_comments_table.php.stub';
        include_once __DIR__.'/Fixtures/Database/migrations/create_categories_table.php.stub';
        include_once __DIR__.'/Fixtures/Database/migrations/create_tags_table.php.stub';
        include_once __DIR__.'/Fixtures/Database/migrations/create_jobs_table.stub';
        include_once __DIR__.'/Fixtures/Database/migrations/create_job_batches_table.stub';

        Schema::dropIfExists('users');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');

        (new \CreateUsersTable())->up();
        (new \CreatePostsTable())->up();
        (new \CreateLikesTable())->up();
        (new \CreateCommentsTable())->up();
        (new \CreateTagsTable())->up();
        (new \CreateCategoriesTable())->up();
        (new \CreateJobsTable())->up();
        (new \CreateJobBatchesTable())->up();
    }

    public function authUser()
    {
        $user = User::forceCreate([
            'name'        => 'User',
            'email'       => 'user@example.com',
            'password'    => '$2y$10$MTibKZXWRvtO2gWpfpsngOp6FQXWUhHPTF9flhsaPdWvRtsyMUlC2',
            'permissions' => json_encode(['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete', 'setStatus']),
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function updateAbilities(array $ability)
    {
        Auth::user()->update(['permissions' => json_encode($ability)]);
    }

    protected function getResourceController(): Controller
    {
        $controller = new Controller(new Manager());

        // TODO: move this to a shared class
        // avoid changing in two places or false positives

        $controller->current = [
            'formation'          => PostFormation::class,
            'resource'           => 'posts',
            'resource_route_key' => 'post',
            'routes'             => [
                ['type' => 'index', 'key' => 'posts.index'],
                ['type' => 'show', 'key' => 'posts.show'],
                ['type' => 'create', 'key' => 'posts.create'],
                ['type' => 'store', 'key' => 'posts.store'],
                ['type' => 'edit', 'key' => 'posts.edit'],
                ['type' => 'update', 'key' => 'posts.update'],
                ['type' => 'delete', 'key' => 'posts.delete'],
                ['type' => 'restore', 'key' => 'posts.restore'],
                ['type' => 'forceDelete', 'key' => 'posts.forceDelete'],
            ],
        ];

        return $controller;
    }
}
