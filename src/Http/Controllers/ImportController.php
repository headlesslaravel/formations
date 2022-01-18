<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Mail\ImportErrorsMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class ImportController
{
    public function store()
    {
        Request::validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $formation = app(Route::current()->parameter('formation'));

        $importable = $formation->importable();
        $importable->import(Request::file('file'));

        if (count($importable->failures())) {
            Mail::to(Auth::user())->send(new ImportErrorsMail($importable->failures()));
        }

        return response()->json(['success' => true]);
    }
}
