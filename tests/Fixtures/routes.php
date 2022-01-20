<?php

use HeadlessLaravel\Formations\Tests\Fixtures\AuthorFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\CategoryFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\PostFormation;
use HeadlessLaravel\Formations\Tests\Fixtures\TagFormation;
use Illuminate\Support\Facades\Route;

Route::get('login')->name('login');

Route::seeker('search', [
    PostFormation::class,
    AuthorFormation::class,
]);

Route::formation(PostFormation::class)->resource('posts');
Route::formation(AuthorFormation::class)->resource('authors');
Route::formation(PostFormation::class)->resource('authors.posts');

Route::formation(TagFormation::class)
    ->resource('posts.tags')
    ->pivot();

Route::formation(PostFormation::class)
    ->resource('posts')
    ->import();

Route::formation(CategoryFormation::class)
    ->resource('categories')
    ->import();
