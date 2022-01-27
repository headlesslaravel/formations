<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

class ExportController
{
    public function create(Request $request)
    {
        /** @var Formation $formation */
        $formation = app(Route::current()->parameter('formation'));

        $exportable = $formation->exportable();
        $requestFields = $request->get('columns');

        if (!empty($requestFields)) {
            $requestFields = explode(',', $requestFields);
            $exportable->fields = collect($formation->export())->filter(function (Field $field) use ($requestFields) {
                return in_array($field->key, $requestFields);
            })->toArray();
        }

        return Excel::download($exportable, $formation->resourceName().'.xlsx');
    }
}
