<?php

namespace HeadlessLaravel\Formations;

use HeadlessLaravel\Formations\Exceptions\PageExceededException;
use HeadlessLaravel\Formations\Exports\Export;
use HeadlessLaravel\Formations\Http\Controllers\NestedController;
use HeadlessLaravel\Formations\Http\Controllers\PivotController;
use HeadlessLaravel\Formations\Http\Controllers\ResourceController;
use HeadlessLaravel\Formations\Http\Requests\CreateRequest;
use HeadlessLaravel\Formations\Http\Requests\UpdateRequest;
use HeadlessLaravel\Formations\Http\Resources\Resource;
use HeadlessLaravel\Formations\Imports\Import;
use HeadlessLaravel\Formations\Scopes\SearchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
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
     * Array of columns allowed to search by.
     *
     * @var array
     */
    public $search = [];

    /**
     * Array of columns allowed to order by.
     *
     * @var array
     */
    public $sort = ['created_at'];

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
     * Build the query upon method injection.
     */
    public function validate()
    {
        Validator::make(
            Request::all(),
            $this->getFilterRules()
        )->validate();
    }

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
     * Get validation rules for url parameters.
     *
     * @var array
     */
    protected function getFilterRules(): array
    {
        $rules = [
            'search'    => 'nullable|string|min:1|max:64',
            'per_page'  => "nullable|integer|min:1,max:{$this->maxPerPage}",
            'sort'      => 'nullable|string|in:'.$this->getSortableKeys(),
            'sort-desc' => 'nullable|string|in:'.$this->getSortableKeys(),
        ];

        $rules = array_merge($rules, $this->rulesForIndexing());

        foreach ($this->filters() as $filter) {
            $filter->setRequest(request());
            foreach ($filter->getRules() as $key => $rule) {
                $rules[$key] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Perform the query.
     *
     * @return Builder
     */
    public function builder()
    {
        $this->applyDefaults();

        $query = app($this->model)->query();
        $query = $this->applySort($query);
        $query = $this->applySearch($query);
        $query = $this->applyIncludes($query);
        $query = $this->applyFilters($query);
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
     * Apply search to the query.
     *
     * @var Builder
     *
     * @return Builder
     */
    protected function applySearch($query)
    {
        if ($term = Request::input('search')) {
            $query = (new SearchScope())->apply($query, $this->search, $term);
        }

        return $query;
    }

    /**
     * Apply filters to the query.
     *
     * @var Builder
     *
     * @return Builder
     */
    protected function applyFilters($query)
    {
        foreach ($this->filters() as $filter) {
            $filter->setRequest(request());
            $filter->apply($query);
        }

        return $query;
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

    /**
     * Apply sort to the query.
     *
     * @var Builder
     *
     * @return Builder
     */
    protected function applySort($query)
    {
        $sortable = $this->getSortable();

        if (empty($sortable)) {
            return $query;
        }

        if (!empty($sortable['relationship'])) {
            $relation = $query->getModel()->{$sortable['relationship']}(); // comments

            $subquery = $relation->getModel() // Comment
                ->select($sortable['column'])  // upvotes
                ->whereColumn(
                    $relation->getQualifiedForeignKeyName(), // comments.post_id
                    $query->getModel()->getQualifiedKeyName() // posts.id
                )->take(1);

            $query->addSelect([
                $sortable['column'] => $subquery,  // upvotes
            ]);
        } elseif (method_exists($query->getModel(), $sortable['column'])) {
            $query->withCount($sortable['column']);
            $sortable['column'] = $sortable['column'].'_count';
        }

        $query->orderBy($sortable['column'], $sortable['direction']);

        return $query;
    }

    public function getSortable(): array
    {
        if (Request::filled('sort')) {
            $sortable = [
                'column'   => Request::input('sort'),
                'direction'=> 'asc',
            ];
        } elseif (Request::filled('sort-desc')) {
            $sortable = [
                'column'   => Request::input('sort-desc'),
                'direction'=> 'desc',
            ];
        } else {
            return [];
        }

        foreach ($this->sort as $definition) {
            if (Str::endsWith($definition, '.'.$sortable['column'])) {
                $sortable['column'] = Str::after($definition, '.');
                $sortable['relationship'] = Str::before($definition, '.');
            } elseif (Str::endsWith($definition, ' as '.$sortable['column'])) {
                $sortable['alias'] = $sortable['column'];
                $sortable['column'] = Str::before($definition, ' as '.$sortable['column']);
                if (Str::contains($sortable['column'], '.')) {
                    $sortable['relationship'] = Str::before($sortable['column'], '.');
                    $sortable['column'] = Str::after($sortable['column'], '.');
                }
            }
        }

        return $sortable;
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

            $meta['sort'] = $this->sort;

            $meta['fields'] = $formatted;

            $meta['filters'] = collect($this->filters())->map(function ($filter) {
                return [
                    'key'       => $filter->publicKey,
                    'display'   => $filter->getDisplay(),
                    'component' => $filter->component,
                    'props'     => $filter->props,
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

    public function rulesForIndexing(): array
    {
        return collect($this->index())->flatMap(function ($field) {
            return [$field->internal => $field->rules ?? 'nullable'];
        })->toArray();
    }

    public function rulesForCreating(): array
    {
        return collect($this->create())->flatMap(function ($field) {
            return [$field->internal => $field->rules ?? 'nullable'];
        })->toArray();
    }

    public function rulesForUpdating(): array
    {
        return collect($this->edit())->flatMap(function ($field) {
            return [$field->internal => $field->rules ?? 'nullable'];
        })->toArray();
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

    public function getSortableKeys()
    {
        $keys = [];

        foreach ($this->sort as $sort) {
            if (Str::contains($sort, ' as ')) {
                $bits = explode(' as ', $sort);
                $keys[] = $bits[1];
            } else {
                if (Str::contains($sort, '.')) {
                    $bits = explode('.', $sort);
                    $keys[] = $bits[1];
                } else {
                    $keys[] = $sort;
                }
            }
        }

        return implode(',', $keys);
    }
}
