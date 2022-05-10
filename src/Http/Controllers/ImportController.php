<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Exports\ImportTemplate;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class ImportController
{
    public function create()
    {
        $formation = app(Route::current()->parameter('formation'));
        $fileName = $formation->resourceName();

        return Excel::download(new ImportTemplate($formation), "$fileName.csv");
    }

    public function store()
    {
        Request::validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $formation = app(Route::current()->parameter('formation'));

        HeadingRowFormatter::default('none');
        $importable = $formation->importable();
        $importable->import(Request::file('file'));
        $importable->confirmation();

        return response()->json(['success' => true]);
    }
}
