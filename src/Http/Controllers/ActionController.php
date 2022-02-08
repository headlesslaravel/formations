<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\LazyCollection;

class ActionController extends Controller
{
    public function store(Request $request)
    {
        $action = $this->formationAction();

        if ($action->ability) {
            $this->check($action->ability, $action->formation->model);
        }

        $validated = $action->validate();

        $query = $action->queryUsing(
            $request->get('selected'),
            $request->get('query', [])
        );

        $batch = Bus::batch([])
            ->allowFailures()
            ->dispatch();

        $query->cursor()
            ->chunk(1000)
            ->each(function (LazyCollection $models) use ($batch, $action, $validated) {
                foreach ($models as $model) {
                    $batch->add(new $action->job($model, $validated['fields']));
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
