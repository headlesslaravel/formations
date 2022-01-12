<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\PostFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\TagFormation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_formation_routes_only()
    {
        // preloaded from routes.php
        $routesFilesRoutes = count(Route::getRoutes());

        Route::formation('articles', PostFormation::class)->only('index');
        Route::formation('articles.tags', TagFormation::class)->only(['index']);
        Route::formation('articles.tags', TagFormation::class)->pivot()->only(['sync']);
        Route::formation('authors.articles', PostFormation::class)->only(['index']);

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertCount(($routesFilesRoutes + 4), $routes);
        $this->assertNotNull($routes->getByName('articles.index'));
        $this->assertNotNull($routes->getByName('authors.articles.index'));
        $this->assertNotNull($routes->getByName('articles.tags.index'));
        $this->assertNotNull($routes->getByName('articles.tags.sync'));
    }

    public function test_formation_routes_except()
    {
        // preloaded from routes.php
        $routesFilesRoutes = count(Route::getRoutes());

        $a = Route::formation('articles', PostFormation::class)->except('index')->create();
        $b = Route::formation('articles.tags', TagFormation::class)->except(['index'])->create();
        $c = Route::formation('articles.tags', TagFormation::class)->pivot()->except(['sync', 'index', 'show'])->create();

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertEquals(count($routes), (count($a) + count($b) + count($c) + $routesFilesRoutes));
        $this->assertNull($routes->getByName('articles.index'));
        $this->assertNull($routes->getByName('articles.tags.index'));
        $this->assertNull($routes->getByName('articles.tags.sync'));
    }
}
