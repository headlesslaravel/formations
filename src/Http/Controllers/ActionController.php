<?php

namespace HeadlessLaravel\Formations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class ActionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $action = $this->formationAction();

        if ($action->ability) {
            $this->check(
                $action->ability,
                $action->formation->model
            );
        }

        $validated = $action->validate();

        $batch = $action->batch(
            $request->get('selected'),
            $request->get('query', []),
            $validated['fields'] ?? []
        );

        return response()->json(['id' => $batch->id]);
    }

    public function progress($batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        return response()->json([
            'status' => $batch->finished() ? 'complete' : 'in-progress',
            'total' => $batch->totalJobs,
            'processed' => $batch->processedJobs(),
        ]);
    }
}
