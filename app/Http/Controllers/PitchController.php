<?php

namespace App\Http\Controllers;

use App\Http\Resources\PitchSectionResource;
use App\Models\Project;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class PitchController extends Controller
{
    // get api/projects/{project}/pitch
    public function index(Request $request, string $id)
    {
        $project = Project::where('device_id', $request->device_id)
            ->findOrFail($id);
        $pitchSections = $project->pitchSections;

        return PitchSectionResource::collection($pitchSections);

    }

    // post api/projects/{project}/pitch
    public function store(Request $request, string $id, GeminiService $geminiService)
    {
        $project = Project::where('device_id', $request->device_id)->findOrFail($id);
        $analysis = $project->analysis;
        $appliedImprovements = $project->Improvements()->where('status', 'applied')->get();
        $pitchSections = [];
        try {
            $pitchData = $geminiService->pitch($project, $analysis, $appliedImprovements);
        } catch (\Exception $e) {
            \Log::error('Gemini pitch failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to generate pitch. Please try again.',
            ], 502);
        }
        $project->pitchSections()->delete();

        foreach ($pitchData['sections'] as $section) {
            $pitchSections[] = $project->pitchSections()->create([
                'section_type' => $section['section_type'],
                'content' => $section['content'],
            ]);
        }
        $project->update([
            'status' => 'pitching',
            'updated_at' => now(),
        ]);

        return PitchSectionResource::collection($pitchSections);
    }

    // patch api/projects/{project}/pitch/{section}
    public function update(Request $request, string $projectId, string $sectionId)
    {
        $project = Project::where('device_id', $request->device_id)->findOrFail($projectId);
        $PitchSection = $project->pitchSections()->findOrFail($sectionId);
        $request->validate([
            'content' => 'required|string',
        ]);
        $PitchSection->update([
            'content' => $request->content,
        ]);
        $project->update([
            'updated_at' => now(),
        ]);

        return new PitchSectionResource($PitchSection);
    }

    // post api/projects/{project}/pitch/{section}/regenerate
    public function regenerate(Request $request, string $projectId, string $sectionId, GeminiService $geminiService)
    {

        $project = Project::where('device_id', $request->device_id)->findOrFail($projectId);
        $PitchSection = $project->pitchSections()->findOrFail($sectionId);
        $analysis = $project->analysis;
        $appliedImprovements = $project->Improvements()->where('status', 'applied')->get();
        try {
            $sectionData = $geminiService->regeneratePitchSection($project, $analysis, $appliedImprovements, $PitchSection->section_type);
        } catch (\Exception $e) {
            \Log::error('Gemini regenerate pitch section failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to regenerate pitch section. Please try again.',
            ], 502);
        }
        $PitchSection->update([
            'content' => $sectionData['content'],
        ]);
        $project->update([
            'updated_at' => now(),
        ]);

        return new PitchSectionResource($PitchSection);
    }
}
