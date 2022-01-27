<?php

use HeadlessLaravel\Formations\Tests\Fixtures\AuthorFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\PostFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\TagFormation;
use Illuminate\Support\Facades\Route;

Route::get('login')->name('login');

Route::seeker([
    PostFormation::class,
    AuthorFormation::class,
], 'search');

Route::formation(PostFormation::class)->resource('posts');
Route::formation(AuthorFormation::class)->resource('authors');
Route::formation(PostFormation::class)->resource('authors.posts');

Route::formation(TagFormation::class)
    ->resource('posts.tags')
    ->asPivot();

Route::formation(PostFormation::class)
    ->resource('posts')
    ->asImport();
