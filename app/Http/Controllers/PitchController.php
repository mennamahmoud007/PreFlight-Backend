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
    public function store(Request $request, string $id, GeminiService $geminiService) {}

    // patch api/projects/{project}/pitch/{section}
    public function update(Request $request, string $projectId, string $sectionId) {}

    // post api/projects/{project}/pitch/{section}/regenerate
    public function regenerate(Request $request, string $projectId, string $sectionId, GeminiService $geminiService) {}
}
