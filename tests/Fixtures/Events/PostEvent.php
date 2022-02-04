<?php

namespace HeadlessLaravel\Formations\Tests\Fixtures\Events;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The post instance.
     *
     * @var Post
     */
    public $post;

    /**
     * Create a new event instance.
     *
     * @param  Post  $post
     * @return void
     */
    public function __construct($post)
    {
        $this->post = $post;
    }
}