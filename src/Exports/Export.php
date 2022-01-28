<?php

namespace HeadlessLaravel\Formations\Exports;

use HeadlessLaravel\Formations\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Export implements FromCollection, WithHeadings
{
    use Exportable;

    /** @var Builder */
    public $builder;

    /** @var Field[] */
    public $fields = [];

    /**
     * @param Builder $builder
     * @param array   $fields
     */
    public function __construct($builder, $fields)
    {
        $this->builder = $builder;
        $this->fields = $fields;
    }

    public function collection(): Collection
    {
        $eagerLoadings = $this->getEagerLoadings();

        $records = $this->builder->with($eagerLoadings)->get();
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
