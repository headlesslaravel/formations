<?php

namespace HeadlessLaravel\Formations\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportSuccessMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var int */
    public $recordsCount;

    /**
     * @param int $recordsCount
     */
    public function __construct($recordsCount)
    {
        $this->recordsCount = $recordsCount;
    }

    public function build(): self
    {
        return $this
            ->subject("$this->recordsCount successfully imported")
            ->html("Here is confirmation that $this->recordsCount rows have been imported successfully");
    }
}
