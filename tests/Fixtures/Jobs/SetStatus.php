<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures\Jobs;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetStatus implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Post */
    public $post;

    /** @var array */
    public $fields;

    public function __construct(Post $post, array $fields)
    {
        $this->post = $post;

        $this->fields = $fields;
    }

    public function handle()
    {
        $this->post->update(['status' => $this->fields['status']]);
    }
}