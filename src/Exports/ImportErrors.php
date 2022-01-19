<?php

namespace HeadlessLaravel\Formations\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportErrors implements FromCollection, WithHeadings
{
    use Exportable;

    public $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function headings(): array
    {
        return array_keys($this->errors[0]);
    }

    public function collection():Collection
    {
        return collect($this->errors);
    }
}
