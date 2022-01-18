<?php

namespace HeadlessLaravel\Formations\Imports;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Formation;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportImportTemplate implements WithHeadings
{
    use Exportable;

    public $fields = [];

    /**
     * @param Formation $formation
     */
    public function __construct($formation)
    {
        $this->fields = $formation->import();
    }

    public function headings(): array
    {
        return collect($this->fields)->map(function (Field $field) {
            return $field->key;
        })->toArray();
    }
}