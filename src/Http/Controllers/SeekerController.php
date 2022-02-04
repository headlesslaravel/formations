<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Finders\Scopes\SearchScope;
use HeadlessLaravel\Formations\Manager;
use Illuminate\Support\Facades\Request;

class SeekerController
{
    public function index()
    {
        $formations = app(Manager::class)->getSeekerFormations();

        return [
            'data' => collect($formations)->map(function ($formation) {
                $formation = app($formation);
                $query = app($formation->model)->query();

                $columns = collect($formation->search())->pluck('internal')->toArray();

                $query = (new SearchScope())->apply($query, $columns, Request::input('term'));

                $data = $query
                    ->select(['id as value', "$formation->display as display"])
                    ->limit(10)
                    ->get()
                    ->map(function ($result) {
                        $result['value'] = (int) $result['value'];

                        return $result;
                    });

                return [
                    'data' => $data,
                    'meta' => $formation->seekerMeta(),
                ];
            })->toArray(),
        ];
    }
}
