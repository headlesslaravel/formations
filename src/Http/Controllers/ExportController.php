<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Field;
use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Rules\ValidColumns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ExportController
{
    public function create(Request $request)
    {
        /** @var Formation $formation */
        $formation = app(Route::current()->parameter('formation'));

        $validator = Validator::make($request->all(), [
            'columns' => ['nullable', new ValidColumns($formation->export())]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $exportable = $formation->exportable();
        $requestedColumns = $request->get('columns');

        if (!empty($requestedColumns)) {
            $requestedColumns = explode(',', $requestedColumns);

            $exportable->fields = collect($formation->export())
                ->filter(function (Field $field) use ($requestedColumns) {
                    return in_array($field->key, $requestedColumns);
                })->toArray();
        }

        return Excel::download($exportable, $formation->exportAs());
    }
}
