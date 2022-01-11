<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use HeadlessLaravel\Formations\Manager;
use HeadlessLaravel\Formations\Scopes\SearchScope;
use Illuminate\Support\Facades\Request;

class SeekerController
{
    public function index()
    {
        $formations = app(Manager::class)->getSeekerFormations();

        return [
            'data' => collect($formations)->map(function($formation)  {
                $formation = app($formation);
                $query = app($formation->model)->query();

                abort_if(count($formation->search) == 0, 500, class_basename($formation) .' formation is missing search');

                $query = (new SearchScope())->apply($query, $formation->search, Request::input('term'));

                $data = $query
                    ->select(['id as value', "$formation->display as display"])
                    ->limit(10)
                    ->get()
                    ->map(function($result) {
                        $result['value'] = (int) $result['value'];
                        return $result;
                    });


                return [
                    'data' => $data,
                    'meta' => $formation->seekerMeta(),
                ];
            })->toArray()
        ];
    }
}
