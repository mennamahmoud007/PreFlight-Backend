<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalysisResource;
use App\Models\Project;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class StressTestController extends Controller
{
    public function store(Request $request, string $id, GeminiService $geminiService)
    {
        $project = Project::where('device_id', $request->device_id)
            ->findOrFail($id);
        // return the exact row from the analysis model related to the project, if it exists, otherwise return null
        $analysis = $project->analysis;
        if (! $analysis) {
            return response()->json([
                'message' => 'Analysis not found for this project.',
            ], 404);
        }
        try {
            $stressTestData = $geminiService->stressTest($project, $analysis);
        } catch (\Exception $e) {
            \Log::error('Gemini analyze failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to perform stress test. Please try again.',
            ], 502);
        }
        $analysis->update($stressTestData);
        $project->update([
            'status' => 'stress-tested',
            'last_checked_at' => now(),
        ]);

        return new AnalysisResource($analysis);

    }
}
