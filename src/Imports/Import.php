<?php

namespace HeadlessLaravel\Formations\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\RowValidator;

class Import implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;
    use Importable;

    public $model;

    public $fields = [];

    /** @var Collection */
    private $replacements;

    public function __construct($model, $fields)
    {
        $this->model = $model;

        $this->fields = $fields;
    }

    public function collection(Collection $rows)
    {
        $this->prepareReplacements($rows);

        /** @var RowValidator $rowValidator */
        $rowValidator = app(RowValidator::class);

        foreach ($rows as $row) {

            try {
                $rowValidator->validate([$row->toArray()], $this);

                $row = $this->applyReplacement($row);
                $model = new $this->model();
                $model->fill($this->values($row));
                $model->save();
            } catch (\Throwable $e) {
                // Do Nothing as failures are already pushed to the importable
            }
        }
    }

    public function applyReplacement(Collection $row)
    {
        foreach ($this->replacements as $replacement) {
            if (isset($row[$replacement['search_key']])) {
                if ($row[$replacement['search_key']] == $replacement['search_value']) {
                    unset($row[$replacement['search_key']]); // author
                    $row[$replacement['replace_key']] = $replacement['replace_value']; // ['author_id'] = 1
                }
            }
        }

        return $row;
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $rules["*.$field->key"] = $field->rules;
        }

        return $rules;
    }

    public function values(Collection $row): array
    {
        $keys = collect($this->fields)->pluck('key');
        $keys = array_merge($keys->toArray(), array_unique($this->replacements->pluck('replace_key')->toArray()));

        return $row->only($keys)->toArray();
    }

    public function prepareReplacements(Collection $rows)
    {
        $this->replacements = collect([]);

        $relations = collect($this->fields)->filter->isRelation();

        // author, category, etc
        foreach ($relations as $relation) {
            $relationshipName = $relation->internal;

            $relationship = app($this->model)->$relationshipName();

            $values = $rows
                ->unique($relation->key)
                ->pluck($relation->key)
                ->toArray();

            $models = $relationship->getModel()
                ->whereIn($relation->relationColumn, $values)
                ->get();

            $display = $relation->relationColumn;

            foreach ($models as $model) {
                $this->replacements->add([
                    'search_key' => $relation->key, // author
                    'search_value' => $model->$display, // frank
                    'replace_key' => $relationship->getForeignKeyName(), // author_id
                    'replace_value' => $model->getKey(), // 1
                ]);
            }
        }
    }
}
