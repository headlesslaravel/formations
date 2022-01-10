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
        Route::formation('articles', PostFormation::class)->only('index');
        Route::formation('articles.tags', TagFormation::class)->only(['index']);
        Route::formation('articles.tags', TagFormation::class)->pivot()->only(['sync']);

        $routes = Route::getRoutes();

        // preloaded from routes.php
        $routesFilesRoutes = 35;

        $this->assertCount(($routesFilesRoutes+3), $routes);
        $this->assertNotNull($routes->getByName('articles.index'));
        $this->assertNotNull($routes->getByName('articles.tags.index'));
        $this->assertNotNull($routes->getByName('articles.tags.sync'));
    }

    public function test_formation_routes_except()
    {
        Route::formation('articles', PostFormation::class)->except('index');
        Route::formation('articles.tags', TagFormation::class)->except(['index']);
        Route::formation('articles.tags', TagFormation::class)->pivot()->except(['sync', 'index', 'show']);

        $routes = Route::getRoutes();

        // preloaded from routes.php
        $routesFilesRoutes = 35;
        $articleRoutes = 8;
        $articleTagsRoutes = 8;
        $articleTagsPivotRoutes = 4;

        $this->assertEquals(count($routes), ($articleRoutes+$articleTagsRoutes+$articleTagsPivotRoutes+$routesFilesRoutes));
        $this->assertNull($routes->getByName('articles.index'));
        $this->assertNull($routes->getByName('articles.tags.index'));
        $this->assertNull($routes->getByName('articles.tags.sync'));
    }
}
