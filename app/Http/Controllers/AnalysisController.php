<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalysisResource;
use App\Models\Project;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    // POST api/projects/{project}/analysis
    public function store(Request $request, string $id, GeminiService $geminiService)
    {
        $project = Project::where('device_id', $request->device_id)
            ->findOrFail($id);
        try {
            $analysisData = $geminiService->analyze($project);
        } catch (\Exception $e) {
            \Log::error('Gemini analyze failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to analyze project. Please try again.',
            ], 502);
        }

        // project->analysis      return the model of the analysis related to the project, if it exists, otherwise return null

        // project->analysis()    return the relationship builder to the analysis model (object), which allows you to perform queries
        //                       and operations on the related analysis record such as updateOrCreate, create, etc.

        $analysis = $project->analysis()->updateOrCreate(
            [],
            $analysisData
        );

        $project->update([
            'status' => 'analyzed',
            'score' => $analysis->overall_score,
            'last_checked_at' => now(),
        ]);

        return new AnalysisResource($analysis);
    }
}
