<?php

namespace App\Http\Controllers;

use App\Http\Resources\ImprovementResource;
use App\Models\Project;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class ImprovementController extends Controller
{
    // post api/projects/{project}/improvement
    public function store(Request $request, string $id, GeminiService $geminiService)
    {
        $project = Project::where('device_id', $request->device_id)
            ->findOrFail($id);
        $analysis = $project->analysis;

        if (! $analysis) {
            return response()->json([
                'message' => 'Analysis not found for this project.',
            ], 404);
        }
        try {
            $improvementData = $geminiService->improvement($project, $analysis);
        } catch (\Exception $e) {
            \Log::error('Gemini improve failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to perform improvement. Please try again.',
            ], 502);
        }

        $project->improvements()->delete();
        $improvements = [];
        foreach ($improvementData['improvements'] as $improvement) {
            $improvements[] = $project->improvements()->create([
                'weakness' => $improvement['weakness'],
                'opportunity' => $improvement['opportunity'],
                'why_it_matters' => $improvement['why_it_matters'],
                'suggested_action' => $improvement['suggested_action'],
                'status' => 'pending',
            ]);
        }
        $project->update([
            'status' => 'improving',
            'last_checked_at' => now(),
        ]);

        return ImprovementResource::collection($improvements);

    }
}
