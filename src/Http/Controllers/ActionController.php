<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Throwable;

class ActionController extends Controller
{
    public function store(Request $request)
    {
        $currentAction = $this->formationAction();

        if (! $currentAction) {
            return redirect()->route('formation.index');
        }

        $modelIds = $request->get('selected', []);

        $models = [];
        if ($modelIds === 'all') {
            $models = $currentAction->formation->results();
        } else if (is_int($modelIds)) {
            $model = $this->model()->where($this->model()->getKeyName(), $modelIds)->first();
            if (!empty($model)) {
                $models = [$model];
            }
        } else if (is_array($modelIds)) {
            $models = $this->model()->whereIn($this->model()->getKeyName(), $modelIds)->get();
        }

        $jobs = [];
        foreach ($models as $model) {
            $jobs[] = new $currentAction->job($model, $currentAction->fields);
        }

        $batch = Bus::batch($jobs)->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->dispatch();

        return response()->json(['id' => $batch->id]);
    }

    public function show($batchId)
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        $result = [];
        $result['status'] = $batch->finished() ? 'complete' : 'in-progress';
        $result['total'] = $batch->totalJobs;
        $result['worked'] = $batch->processedJobs();

        return response()->json($result);
    }
}
