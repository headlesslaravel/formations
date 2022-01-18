<?php

namespace HeadlessLaravel\Formations\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class ImportErrors extends Mailable
{
    use Queueable;
    use SerializesModels;

    protected $failures;

    public function __construct($failures)
    {
        $this->failures = $failures;
    }

    public function build(): self
    {
        return $this
            ->subject('Your import has validation errors')
            ->html('See attached')
            ->attachData($this->errorExport(), 'errors.csv');
    }

    public function errorExport(): string
    {
        $errors = $this->prepareErrors();

        return Excel::raw(new ExportErrors($errors), \Maatwebsite\Excel\Excel::CSV);
    }

    public function prepareErrors(): array
    {
        $attachment = [];

        foreach (collect($this->failures) as $failure) {
            $key = 'row-'.$failure->row();

            $errors = implode(' ', $failure->errors());

            if (isset($attachment[$key])) {
                $attachment[$key]['errors'] .= ' '.$errors;
            } else {
                $attachment[$key] = array_merge(
                    $failure->values(),
                    ['errors' => $errors]
                );
            }
        }

        return array_values($attachment);
    }
}

class ExportErrors implements FromCollection, WithHeadings
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
