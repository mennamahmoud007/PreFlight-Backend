<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // get api/projects
    public function index(Request $request)
    {
        $query = Project::where('device_id', $request->device_id);

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->get();

        return ProjectResource::collection($projects);

    }

    /**
     * Store a newly created resource in storage.
     */
    // post api/projects
    public function store(StoreProjectRequest $request)
    // ... mean that the request validated data will be merged with the device_id and status fields to create a new project
    {
        $project = Project::create([
            ...$request->validated(),
            'device_id' => $request->device_id,
            'status' => 'checking',
        ]);

        return new ProjectResource($project);
    }

    /**
     * Display the specified resource.
     */
    // get api/projects/{project}
    public function show(Request $request, string $id)
    {
        $project = Project::where('device_id', $request->device_id)
            ->with([
                'analysis',
                'improvements',
                'pitchSections',
            ])
            ->findOrFail($id);

        return new ProjectResource($project);
    }

    /**
     * Update the specified resource in storage.
     */
    // patch api/projects/{project}
    public function update(UpdateProjectRequest $request, string $id)
    {
        $project = Project::where('device_id', $request->device_id)
            ->findOrFail($id);
        $project->update($request->validated());

        return new ProjectResource($project);
    }

    /**
     * Remove the specified resource from storage.
     */
    // delete api/projects/{project}
    public function destroy(Request $request, string $id)
    {
        $project = Project::where('device_id', $request->device_id)
            ->findOrFail($id);
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
