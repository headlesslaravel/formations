<?php

namespace HeadlessLaravel\Formations\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportErrors implements FromCollection, WithHeadings
{
    use Exportable;

    public $errors;

    public function __construct($errors)
    {
        $this->errors = $errors;
    }

    public function headings(): array
    {
        return array_keys($this->errors[0]);
    }

    public function collection()
    {
        return collect($this->errors);
    }
}
