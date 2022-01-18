<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Formation;
use HeadlessLaravel\Formations\Imports\ExportImportTemplate;
use HeadlessLaravel\Formations\Mail\ImportErrorsMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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

    public function create()
    {
        /** @var Formation $formation */
        $formation = app(Route::current()->parameter('formation'));

        // TODO: replace this with the PR #50 Manager->resourceByFormation() once it's merged
        $fileName = Str::of(class_basename($formation))
            ->replace('Formation', '')
            ->snake()
            ->plural();

        return Excel::download(new ExportImportTemplate($formation), "$fileName.csv");
    }
}
