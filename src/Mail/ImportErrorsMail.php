<?php

namespace HeadlessLaravel\Formations\Mail;

use HeadlessLaravel\Formations\Exports\ImportErrors;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ImportErrorsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $errors;

    public function __construct($failures)
    {
        $this->errors = $this->resolveErrors($failures);
    }

    public function build(): self
    {
        $count = count($this->errors);

        return $this
            ->subject("Your import has $count invalid rows")
            ->html('See attached')
            ->attachData($this->errorExport(), 'errors.csv');
    }

    public function errorExport(): string
    {
        return Excel::raw(
            new ImportErrors($this->errors),
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    public function resolveErrors($failures): array
    {
        $attachment = [];

        foreach (collect($failures) as $failure) {
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
