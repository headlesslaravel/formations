<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Import;
use HeadlessLaravel\Formations\Mail\ImportErrors;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ImportController
{
    public function store()
    {
        Request::validate(['file' => ['required', 'mimes:csv']]);

        $formation = app(Route::current()->parameter('formation'));

        try {
            Excel::import($formation->importable(), Request::file('file'));
        } catch (ValidationException $e) {
            Mail::to(Auth::user())->send(new ImportErrors($e->failures()));
        }
    }
}
