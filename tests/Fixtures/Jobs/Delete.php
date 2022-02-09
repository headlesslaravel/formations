<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Delete implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var Model */
    public $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $this->model->delete();
    }
}
