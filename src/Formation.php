<?php

namespace HeadlessLaravel\Formations;

use HeadlessLaravel\Finders\ApplyFilters;
use HeadlessLaravel\Finders\ApplySearch;
use HeadlessLaravel\Finders\ApplySort;
use HeadlessLaravel\Formations\Exceptions\PageExceededException;
use HeadlessLaravel\Formations\Exports\Export;
use HeadlessLaravel\Formations\Fields\Field;
use HeadlessLaravel\Formations\Http\Controllers\NestedController;
use HeadlessLaravel\Formations\Http\Controllers\PivotController;
use HeadlessLaravel\Formations\Http\Controllers\ResourceController;
use HeadlessLaravel\Formations\Http\Requests\CreateRequest;
use HeadlessLaravel\Formations\Http\Requests\UpdateRequest;
use HeadlessLaravel\Formations\Http\Resources\Resource;
use HeadlessLaravel\Formations\Imports\Import;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Formation
{
    use Concerns\HasData;
    use Concerns\HasQueries;

    /**
     * The select option display column.
     *
     * @var string
     */
    public $display = 'id';

    /**
     * The foreign key override.
     *
     * @var string
     */
    public $foreignKey;

    /**
     * The maximum number of items per page.
     *
     * @var int
     */
    public $maxPerPage = 100;

    /**
     * The default parameters.
     *
     * @var mixed
     */
    public $defaults = [];

    /**
     * The given parameters.
     *
     * @var mixed
     */
    public $given = [];

    /**
     * The select overrides.
     *
     * @var mixed
     */
    public $select = [];

    /**
     * The model instance.
     *
     * @var Model
     */
    public $model;

    /**
     * The detail route name.
     */
    public $detailRouteName;

    /**
     * The global search.
     */
    public $globalSearch = true;

    /**
     * The resource controller.
     *
     * @var string
     */
    public $controller = ResourceController::class;

    /**
     * The nested resource controller.
     *
     * @var string
     */
    public $nestedController = NestedController::class;

    /**
     * The pivot resource controller.
     *
     * @var string
     */
    public $pivotController = PivotController::class;

    /**
     * The default create request.
     *
     * @var string
     */
    public $create = CreateRequest::class;

    /**
     * The default update request.
     *
     * @var string
     */
    public $update = UpdateRequest::class;

    /**
     * The default api resource.
     *
     * @var string
     */
    public $resource = Resource::class;

    /**
     * The default import class.
     *
     * @var string
     */
    public $import = Import::class;

    /**
     * The default export class.
     *
     * @var string
     */
    public $export = Export::class;

    /**
     * The results.
     *
     * @var mixed
     */
    protected $results = [];

    /**
     * The conditions.
     *
     * @var array
     */
    protected $conditions = [];

    /**
     * If request was called.
     *
     * @var bool
     */
    protected $wasRequested = false;

    /**
     * Perform the query.
     *
     * @return Collection
     */
    public function results()
    {
        if ($this->wasRequested) {
            return $this->results;
        }

        $query = $this->builder();

        $this->indexQuery($query);

        $this->results = $query
            ->paginate($this->perPage())
            ->withQueryString();

        $this->validatePagination();

        $this->wasRequested = true;

        return $this->results;
    }

    public function perPage()
    {
        $perPage = Request::input('per_page', app($this->model)->getPerPage());

        if ($perPage > $this->maxPerPage) {
            $perPage = $this->maxPerPage;
        }

        return $perPage;
    }

    /**
     * Perform the query.
     *
     * @return Builder
     */
    public function builder()
    {
        $this->applyDefaults();
        $this->applyGiven();

        $query = app($this->model)->query();

        ApplySort::on($query, $this->sort());
        ApplySearch::on($query, $this->search());
        ApplyFilters::on($query, $this->filters());

        $query = $this->applyIncludes($query);
        $query = $this->applySelect($query);
        $query = $this->applyConditions($query);

        return $query;
    }

    /**
     * Apply defaults to the request.
     *
     * @return self
     */
    protected function applyDefaults(): self
    {
        foreach ($this->defaults as $key => $value) {
            if (!Request::has($key)) {
                Request::merge([$key => $value]);
            }
        }

        return $this;
    }

    /**
     * Set given params.
     *
     * @param array $params
     *
     * @return self
     */
    public function given($params = []): self
    {
        $this->given = $params;

        return $this;
    }

    /**
     * Apply given params to the request.
     *
     * @return self
     */
    protected function applyGiven(): self
    {
        foreach ($this->given as $key => $value) {
            Request::merge([$key => $value]);
        }

        return $this;
    }

    /**
     * Apply includes to the query.
     *
     * @var Builder
     *
     * @return Builder
     */
    protected function applyIncludes($query)
    {
        foreach ($this->includes() as $include) {
            if ($include->isActive()) {
                $include->apply($query);
            }
        }

        return $query;
    }

    /**
     * Apply conditions to the query.
     *
     * @var Builder
     *
     * @return Builder
     */
    protected function applyConditions($query)
    {
        foreach ($this->conditions as $arguments) {
            $query->where(...$arguments);
        }

        return $query;
    }

    /**
     * Apply selects to the query.
     *
     * @var Builder
     *
     * @return Builder
     */
    protected function applySelect($query)
    {
        if (count($this->select)) {
            return $query->select($this->select);
        }

        return $query;
    }

    public function validatePagination()
    {
        if (Request::input('page') > $this->results->lastPage()) {
            throw new PageExceededException();
        }
    }

    public function guessResourceName(): Stringable
    {
        return Str::of(class_basename($this))
            ->replace('Formation', '')
            ->snake()
            ->slug()
            ->plural();
    }

    public function resourceName(): Stringable
    {
        /** @var Manager $manager */
        $manager = app(Manager::class);

        $resource = $manager->resourceByFormation(get_class($this));

        if (!empty($resource)) {
            return new Stringable($resource['resource']);
        }

        return $this->guessResourceName();
    }

    public function seekerMeta()
    {
        $name = $this->resourceName();

        if ($this->detailRouteName) {
            $route = $this->detailRouteName;
        } else {
            $route = new Stringable();
            $prefix = Request::route()->getPrefix();
            if (!empty($prefix)) {
                $route = $route->prepend($prefix.'.');
            }
            $route = $route->append($name->append('.show'));
        }

        return [
            'route'    => $route,
            'resource' => $name,
        ];
    }

    public function meta($type): array
    {
        $fields = $this->$type();

        $meta = [
            'resource'          => $this->resourceName(),
            'resource_singular' => Str::singular($this->resourceName()),
        ];

        if ($type === 'index') {
            $formatted = [];

            foreach ($fields as $field) {
                if ($field->key == $field->internal) {
                    $formatted[] = $field->key;
                } else {
                    $formatted[] = "$field->internal:$field->key";
                }
            }

            $meta['sort'] = collect($this->sort())->pluck('key')->toArray();

            $meta['fields'] = $formatted;

            $meta['filters'] = collect($this->filters())->reject->hidden->map(function ($filter) {
                return [
                    'key'       => $filter->publicKey,
                    'display'   => $filter->getDisplay(),
                    'component' => $filter->component,
                    'props'     => $filter->props,
                    'modifiers' => $filter->modifiers,
                ];
            })->toArray();

            $meta['slices'] = collect($this->slices())->map(function (Slice $slice) {
                return array_merge([
                    'display'   => $slice->key,
                    'link'      => $slice->internal,
                ], $slice->filters);
            })->toArray();
        }

        if ($type === 'create' || $type === 'edit') {
            $formatted = [];

            foreach ($fields as $field) {
                $formatted[] = "$field->internal:$field->key";
            }

            $meta['fields'] = $formatted;
        }

        return $meta;
    }

    /**
     * Define the fields all views.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Field::make('Id', 'id'),
            //
            Field::make('Created', 'created_at'),
            Field::make('Updated', 'updated_at'),
        ];
    }

    /**
     * Define the fields for indexing.
     *
     * @return array
     */
    public function index(): array
    {
        return $this->fields();
    }

    /**
     * Define the fields for showing.
     *
     * @return array
     */
    public function show(): array
    {
        return $this->index();
    }

    /**
     * Define the fields for create forms.
     *
     * @return array
     */
    public function create(): array
    {
        return $this->form();
    }

    /**
     * Define the fields for edit forms.
     *
     * @return array
     */
    public function edit(): array
    {
        return $this->form();
    }

    /**
     * Define the fields for forms.
     *
     * @return array
     */
    public function form(): array
    {
        return collect($this->fields())->filter(function ($field) {
            return !in_array($field->internal, [
                'id', 'created_at', 'updated_at', 'deleted_at',
            ]);
        })->toArray();
    }

    public function rules(): array
    {
        return [];
    }

    public function getResolvedIndexFields(): array
    {
        return collect($this->index())->map->render($this, 'index')->toArray();
    }

    public function getResolvedCreateFields(): array
    {
        return collect($this->create())->map->render($this, 'create')->toArray();
    }

    public function getResolvedEditFields(): array
    {
        return collect($this->edit())->map->render($this, 'edit')->toArray();
    }

    public function rulesForIndexing(): array
    {
        return collect($this->getResolvedIndexFields())->flatMap(function ($field) {
            return [$field->internal => $field->rules ?? 'nullable'];
        })->toArray();
    }

    public function rulesForCreating(): array
    {
        return collect($this->getResolvedCreateFields())->flatMap(function ($field) {
            return [$field->internal => $field->rules ?? 'nullable'];
        })->toArray();
    }

    public function rulesForUpdating(): array
    {
        return collect($this->getResolvedEditFields())->flatMap(function ($field) {
            return [$field->internal => $field->rules ?? 'nullable'];
        })->toArray();
    }

    /**
     * Define the search.
     *
     * @return array
     */
    public function search(): array
    {
        return [];
    }

    /**
     * Define the sort.
     *
     * @return array
     */
    public function sort(): array
    {
        return [];
    }

    /**
     * Define the filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Define the slices.
     *
     * @return array
     */
    public function slices(): array
    {
        return [];
    }

    /**
     * @return Slice|null
     */
    public function currentSlice()
    {
        $currentRouteName = Request::route()->getName();

        $slices = $this->slices();

        /** @var Slice $slice */
        foreach ($slices as $slice) {
            $routeName = $this->resourceName().'.slices.'.$slice->internal;
            if ($routeName === $currentRouteName) {
                return $slice->setFormation($this);
            }
        }

        return null;
    }

    /**
     * @return Action|null
     */
    public function currentAction()
    {
        $currentRouteName = Request::route()->getName();

        $actions = $this->actions();

        /** @var Action $action */
        foreach ($actions as $action) {
            $actionRouteName = $this->resourceName().'.actions.'.$action->key;
            $routeNames = [$actionRouteName.'.store', $actionRouteName.'.show'];
            if (in_array($currentRouteName, $routeNames)) {
                return $action->setFormation($this);
            }
        }

        return null;
    }

    /**
     * Define actions.
     *
     * @return array
     */
    public function actions(): array
    {
        return [];
    }

    /**
     * Define the include columns.
     *
     * @return array
     */
    public function includes(): array
    {
        return [];
    }

    /**
     * Define the exportable columns.
     *
     * @return array
     */
    public function import(): array
    {
        return [];
    }

    /**
     * Define the exportable columns.
     *
     * @return array
     */
    public function export(): array
    {
        return [];
    }

    /**
     * Define the export filename.
     *
     * @return array
     */
    public function exportAs(): string
    {
        return (string) $this->resourceName()
            ->append('_')
            ->append(now()->format(config('headless-formations.exports.date_format')))
            ->append('.')
            ->append(config('headless-formations.exports.file_format'));
    }

    public function importable()
    {
        $import = $this->import;

        return new $import($this->model, $this->import());
    }

    public function exportable()
    {
        $export = $this->export;

        return new $export($this->builder(), $this->export());
    }

    public function where(...$arguments): Formation
    {
        $this->conditions[] = $arguments;

        return $this;
    }

    public function whereRelation($relation, $column, $operator, $value): Formation
    {
        return $this->where(function ($query) use ($relation, $column, $operator, $value) {
            $query->whereRelation($relation, $column, $operator, $value);
        });
    }

    public function nest(Formation $formation, $value): Formation
    {
        $this->where($formation->getForeignKey(), $value);

        return $this;
    }

    public function getForeignKey()
    {
        if ($this->foreignKey) {
            return $this->foreignKey;
        }

        return app($this->model)->getForeignKey();
    }

    public function select(array $select): Formation
    {
        $this->select = $select;

        return $this;
    }

    public function options(): Formation
    {
        return $this->select([
            $this->display.' as display',
            app($this->model)->getKeyName().' as value',
        ]);
    }
}
