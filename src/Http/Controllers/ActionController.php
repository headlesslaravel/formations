<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\LazyCollection;

class ActionController extends Controller
{
    public function store(Request $request)
    {
        $currentAction = $this->formationAction();

        if (!$currentAction) {
            return redirect()->route('formation.index');
        }

        if ($currentAction->ability) {
            $this->check($currentAction->ability, $currentAction->formation->model);
        }

        $rules = [];
        foreach ($currentAction->fields as $fieldName => $rule) {
            $rules['fields.'.$fieldName] = $rule;
        }
        $validated = $request->validate($rules);

        $modelIds = $request->get('selected');

        $modelsQuery = $currentAction->formation->builder();

        if (is_int($modelIds)) {
            $modelsQuery = $modelsQuery->where($this->model()->getKeyName(), $modelIds);
        } elseif (is_array($modelIds)) {
            $modelsQuery = $modelsQuery->whereIn($this->model()->getKeyName(), $modelIds);
        }

        $batch = Bus::batch([])
            ->allowFailures()
            ->dispatch();

        $modelsQuery->cursor()
            ->chunk(1000)
            ->each(function (LazyCollection $models) use ($batch, $currentAction, $validated) {
                foreach ($models as $model) {
                    $batch->add(new $currentAction->job($model, $validated['fields']));
                }
            });

        return response()->json(['id' => $batch->id]);
    }

    public function progress($batchId)
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        $result = [];
        $result['status'] = $batch->finished() ? 'complete' : 'in-progress';
        $result['total'] = $batch->totalJobs;
        $result['processed'] = $batch->processedJobs();

        return response()->json($result);
    }
}
