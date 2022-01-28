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
        $formation = app(Route::current()->parameter('formation'));

        $request->validate(['columns' => ['nullable', new ValidColumns($formation->export())]]);

        $exportable = $formation->exportable();

        if ($columns = $request->get('columns')) {
            $columns = explode(',',$columns);
            $exportable->fields = collect($formation->export())
                ->filter(function (Field $field) use ($columns) {
                    return in_array($field->key, $columns);
                })->toArray();
        }

        return Excel::download($exportable, $formation->exportAs());
    }
}
