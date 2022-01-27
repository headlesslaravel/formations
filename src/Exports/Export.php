<?php

namespace HeadlessLaravel\Formations\Exports;

use HeadlessLaravel\Formations\Field;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Export implements FromCollection, WithHeadings
{
    use Exportable;

    /** @var string */
    public $model;

    /** @var Field[] */
    public $fields = [];

    public function __construct($model, $fields)
    {
        $this->model = $model;
        $this->fields = $fields;
    }

    public function collection(): Collection
    {
        $eagerLoadings = $this->getEagerLoadings();

        $records = (new $this->model())->query()->with($eagerLoadings)->get();
        $result = collect([]);
        foreach ($records as $record) {
            $resultRecord = [];
            foreach ($this->fields as $field) {
                if ($field->isRelation()) {
                    $resultRecord[$field->key] = $record->{$field->internal}->{$field->relationColumn};
                } else {
                    $resultRecord[$field->key] = $record->{$field->key};
                }
            }
            $result->add($resultRecord);
        }

        return $result;
    }

    public function getEagerLoadings(): array
    {
        $relations = collect($this->fields)->filter->isRelation();

        $relationNames = [];
        foreach ($relations as $relation) {
            $relationNames[] = $relation->internal;
        }

        return $relationNames;
    }

    public function headings(): array
    {
        return collect($this->fields)->pluck('key')->toArray();
    }
}
