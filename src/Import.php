<?php

namespace HeadlessLaravel\Formations;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class Import implements ToCollection, WithHeadingRow, WithValidation
{
    public $model;

    public $fields = [];

    public function __construct($model, $fields)
    {
        $this->model = $model;

        $this->fields = $fields;
    }

    public function collection(Collection $rows)
    {
        $rows = $this->prepare($rows);

        foreach($rows as $row) {
            $model = new $this->model;
            $model->fill($this->values($row));
            $model->save();
        }
    }

    public function prepare(Collection $rows):Collection
    {
        $replacements = [];

        $relations = collect($this->fields)->filter->isRelation();

        // author, category, etc
        foreach($relations as $relation)
        {
            $relationshipName = $relation->key;

            $relationship = app($this->model)->$relationshipName();

            $values = $rows
                ->unique($relation->key)
                ->pluck($relation->key)
                ->toArray();

            $models = $relationship->getModel()
                ->whereIn($relation->relation, $values)
                ->get();

            $display = $relation->relation;

            foreach($models as $model) {
                $replacements[] = [
                    'search_key' => $relation->key, // author
                    'search_value' => $model->$display, // frank
                    'replace_key' => $relationship->getForeignKeyName(), // author_id
                    'replace_value'=> $model->getKey(), // 1
                ];
            }
        }

        foreach($replacements as $replacement) {
            $rows->where(
                $replacement['search_key'], // author
                $replacement['search_value'] // frank
            )->each(function($row) use($replacement) {
                unset($row[$replacement['search_key']]); // author
                $row[$replacement['replace_key']] = $replacement['replace_value']; // ['author_id'] = 1
                // change author to author_id in fields for validation keys in value()
                foreach($this->fields as $field) {
                    if($field->key == $replacement['search_key']) {
                        $field->key = $replacement['replace_key'];
                    }
                }
            });
        }

        return $rows;
    }

    public function rules():array
    {
        $rules = [];

        foreach($this->fields as $field) {
            $rules["*.$field->key"] = $field->rules;
        }

        return $rules;
    }

    public function values($row):array
    {
        $keys = collect($this->fields)->pluck('key');

        return $row->only($keys)->toArray();
    }
}
