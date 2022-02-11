<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures;

use HeadlessLaravel\Finders\Filter;
use HeadlessLaravel\Finders\Search;
use HeadlessLaravel\Finders\Sort;
use HeadlessLaravel\Formations\Action;
use HeadlessLaravel\Formations\Fields\Field;
use HeadlessLaravel\Formations\Fields\Select;
use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Slice;
use HeadlessLaravel\Formations\Tests\Fixtures\Jobs\SetStatus;
use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;

class PostFormation extends Formation
{
    public $model = Post::class;

    public $display = 'title';

    public $defaults = [
        'sort-desc' => 'body',
    ];

    public function rulesForIndexing(): array
    {
        return [
            'rule_test' => 'nullable|in:allowed-value',
        ];
    }

    public function editData($model): array
    {
        return [
            'id'       => $model->id,
            'override' => 'populated from override method',
        ];
    }

    public function extraCreateData(): array
    {
        return [
            'extra' => 'populated from extra method',
        ];
    }

    public function search(): array
    {
        return [
            Search::make('title'),
            Search::make('comments.body'),
            Search::make('tags.title'),
        ];
    }

    public function sort(): array
    {
        return [
            Sort::make('title'),
            Sort::make('body'),
            Sort::make('comments'),
            Sort::make('upvotes', 'comments.upvotes'),
            Sort::make('disliked', 'comments.downvotes'),
            Sort::make('author_name', 'author.name'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::make('id'),
            Filter::make('author_id')->multiple(),
            Filter::make('like')->exists()->auth(),
            Filter::make('length')->range(),
            Filter::make('author')->relation(),
            Filter::make('writer', 'author')->relation()->multiple(),
            Filter::make('active')->boolean(),
            Filter::make('toggle', 'active')->toggle(),
            Filter::make('comments')->exists(),
            Filter::make('comments')->count(),
            Filter::make('comments')->countRange(),
            Filter::make('tagged', 'tags')->relation()->multiple(),
            Filter::make('tags')->exists(),
            Filter::make('tags')->count(),
            Filter::make('tags')->countRange(),
            Filter::make('published_at')->date(),
            Filter::make('multiple_dates', 'published_at')->date()->multiple(),
            Filter::make('created_at')->dateRange(),
            Filter::make('status')->options(['active', 'inactive']),
            Filter::make('multiple', 'status')->options(['active', 'inactive'])->multiple(),
            Filter::make('value-scope')->scope('status'),
            Filter::make('active-scope')->scope('active'),
            Filter::make('boolean-scope')->scopeBoolean('activeBoolean'),
            Filter::make('trashed')->trashOnly(),
            Filter::make('with-trashed')->trashIncluded(),
            Filter::make('written-by')->search(['author.name']),
            Filter::make('article-size', 'length')
                ->when('50', function ($query) {
                    $query->where('length', '50');
                })->when('100', function ($query) {
                    $query->where('length', '100');
                }),

            Filter::make('length-range', 'length')
                ->between('small', [1, 10])
                ->between('medium', [11, 20])
                ->between('large', [21, 30]),

            Filter::make('length-range', 'length')
                ->between('small', [1, 10])
                ->between('medium', [11, 20])
                ->between('large', [21, 30]),

            Filter::make('money', 'length')->asCents(),

            Filter::radius(),

            Filter::bounds(),
        ];
    }

    public function slices(): array
    {
        return [
            Slice::make('Active Posts', 'active-posts')
                ->filter(['active' => 'true']),

            Slice::make('InActive Posts')
                ->filter(['active' => 'false']),

            Slice::make('My Posts', 'my-posts')
                ->query(function ($query) {
                    $query->where('author_id', auth()->id());
                }),

            Slice::make('Active Posts Sort Title Desc')
                ->filter([
                    'active'    => 'true',
                    'sort-desc' => 'title',
                ]),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('set-status')
                ->job(SetStatus::class)
                ->can('setStatus')
                ->fields([
                    Field::make('status')->rules(['in:active,draft']),
                ]),
        ];
    }

    public function import(): array
    {
        return [
            Field::make('title')->rules(['required', 'min:2']),
            Field::make('body')->rules(['required', 'min:2']),
            Field::make('author.name')->rules(['required', 'exists:users,name']),
            Field::make('category', 'category.title')->rules(['required', 'exists:categories,title']),
        ];
    }

    public function export(): array
    {
        return [
            Field::make('id'),
            Field::make('title'),
            Field::make('author_name', 'author.name'),
        ];
    }

    public function fields(): array
    {
        return [
            Field::make('Title')->rules(['required', 'min:10']),
            Field::make('Author', 'author_id')->rules(['exists:users,id']),
            Select::make('Status')->options(function() {
                return ['active', 'draft'];
            }),
        ];
    }
}
