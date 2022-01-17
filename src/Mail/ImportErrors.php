<?php

namespace HeadlessLaravel\Formations\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ImportErrors extends Mailable
{
    use Queueable, SerializesModels;

    protected $failures;

    public function __construct($failures)
    {
        $this->failures = $failures;
    }

    public function build():self
    {
        return $this
            ->subject('Your import has validation errors')
            ->html('See attached')
            ->attachData($this->errorExport(), 'errors.csv');
    }

    public function errorExport():String
    {
        $errors = $this->prepareErrors();

        return Excel::raw($errors, \Maatwebsite\Excel\Excel::CSV);
    }

    public function prepareErrors():array
    {
        $attachment = [];

        foreach(collect($this->failures)->groupBy('row') as $row) {

            $errors = '';

            foreach($row as $failure) {
                $errors .= ' ' . implode(' ', $failure->errors());
            }

            $attachment[] = array_merge(
                $row[0]->values(),
                ['errors' => trim($errors)],
            );
        }

        return $attachment;
    }
}
