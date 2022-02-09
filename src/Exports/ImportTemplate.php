<?php

namespace HeadlessLaravel\Formations\Exports;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Formation;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportTemplate implements WithHeadings
{
    use Exportable;

    public $fields = [];

    public function __construct(Formation $formation)
    {
        $this->fields = $formation->import();
    }

    public function headings(): array
    {
        return collect($this->fields)
            ->filter(function (Field $field) {
                return empty($field->type);
            })->map(function (Field $field) {
                return $field->key;
            })->toArray();
    }
}
