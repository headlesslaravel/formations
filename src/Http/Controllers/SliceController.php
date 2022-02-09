<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

class SliceController extends Controller
{
    public function index()
    {
        $this->check('viewAny', $this->model());

        $currentSlice = $this->slice();

        if (!is_null($currentSlice)) {
            $currentSlice->apply();
        }

        return $this->response(
            'index',
            $currentSlice->formation->results()
        );
    }
}
