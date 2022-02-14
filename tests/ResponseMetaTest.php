<?php

namespace HeadlessLaravel\Formations\Tests;

use HeadlessLaravel\Formations\Tests\Fixtures\Models\Post;
use Inertia\Inertia;

class ResponseMetaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('headless-formations.mode', 'inertia');

        Inertia::setRootView('testing::app');
    }

    public function getPostFormationData($method)
    {
        request()->headers->set('X-Inertia', true);

        return $this->getResourceController()
            ->response($method, Post::query()->paginate())
            ->toResponse(request())
            ->getOriginalContent();
    }

    public function test_inertia_index_slice_meta()
    {
        $data = $this->getPostFormationData('index');

        $this->assertEquals('Active Posts', $data['props']['headless']['slices'][0]['display']);
        $this->assertEquals('http://localhost/posts/active-posts', $data['props']['headless']['slices'][0]['link']);

        $this->assertEquals('InActive Posts', $data['props']['headless']['slices'][1]['display']);
        $this->assertEquals('http://localhost/posts/inactive-posts', $data['props']['headless']['slices'][1]['link']);

        $this->assertEquals('My Posts', $data['props']['headless']['slices'][2]['display']);
        $this->assertEquals('http://localhost/posts/my-posts', $data['props']['headless']['slices'][2]['link']);
    }

    public function test_inertia_index_field_meta()
    {
        $data = $this->getPostFormationData('index');

        $fields = $data['props']['headless']['fields'];

        $this->assertEquals('Title', $fields[0]['display']);
        $this->assertEquals('title', $fields[0]['key']);
        $this->assertEquals('Text', $fields[0]['component']);
        $this->assertEquals(true, $fields[0]['sortable']);

        // Body Field excluded

        $this->assertEquals('Author', $fields[2]['display']);
        $this->assertEquals('author_id', $fields[2]['key']);
        $this->assertEquals('Text', $fields[2]['component']);
        $this->assertEquals(true, $fields[0]['sortable']);

        $this->assertEquals('Status', $fields[3]['display']);
        $this->assertEquals('status', $fields[3]['key']);
        $this->assertEquals('Text', $fields[3]['component']);
        $this->assertEquals('active', $fields[3]['props']['options'][0]);
        $this->assertEquals(false, $fields[3]['sortable']);
    }

    public function test_inertia_create_field_meta()
    {
        $data = $this->getPostFormationData('create');

        $fields = $data['props']['headless']['fields'];

        $this->assertEquals('Title', $fields[0]['display']);
        $this->assertEquals('title', $fields[0]['key']);
        $this->assertEquals('Text', $fields[0]['component']);
        $this->assertEquals(true, $fields[0]['sortable']);

        $this->assertEquals('Body', $fields[1]['display']);
        $this->assertEquals('body', $fields[1]['key']);
        $this->assertEquals('Textarea', $fields[1]['component']);
        $this->assertEquals(true, $fields[1]['sortable']);

        $this->assertEquals('Author', $fields[2]['display']);
        $this->assertEquals('author_id', $fields[2]['key']);
        $this->assertEquals('Picker', $fields[2]['component']);
        $this->assertEquals(true, $fields[0]['sortable']);

        // Status field excluded in edit
    }
}
