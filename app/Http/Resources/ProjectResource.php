<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'target_audience' => $this->target_audience,
            'industry' => $this->industry,
            'status' => $this->status,
            'score' => $this->score,
            'last_checked_at' => $this->last_checked_at,

            'analysis' => new AnalysisResource(
                $this->whenLoaded('analysis')
            ),

            'improvements' => ImprovementResource::collection(
                $this->whenLoaded('improvements')
            ),

            'pitch_sections' => PitchSectionResource::collection(
                $this->whenLoaded('pitchSections')
            ),
        ];
    }
}
