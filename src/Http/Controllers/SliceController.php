<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Formation;
use Illuminate\Http\Request;

class SliceController extends Controller
{
    public function index(Request $request)
    {
        $this->check('viewAny', $this->model());

        /** @var Formation $currentFormation */
        $currentFormation = $this->formation();
        $currentFormation->validate();
        $currentSlice = $currentFormation->currentSlice();

        if (!is_null($currentSlice)) {
            if (count($currentSlice->filters)) {
                $input = $request->all();
                $mergedInput = array_merge($currentSlice->filters, $input);
                $request->replace($mergedInput);
            }

            if (count($currentSlice->queries)) {
                $currentFormation->where(function ($query) use ($currentSlice) {
                    $currentSlice->applyQuery($query);
                });
            }
        }

        return $this->response(
            'index',
            $currentFormation->results()
        );
    }
}
