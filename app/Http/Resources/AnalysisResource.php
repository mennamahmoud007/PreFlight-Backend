<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
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
            'project_id' => $this->project_id,
            'score' => [
                'problem' => $this->problem_score,
                'target' => $this->target_score,
                'value' => $this->value_score,
                'feasibility' => $this->feasibility_score,
                'differentiation' => $this->differentiation_score,
                'overall' => $this->overall_score,
            ],
            'summary' => $this->summary,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'risks' => $this->risks,

            'stress_test' => [
                'primary_concern' => $this->primary_concern,
                'critical_questions' => $this->critical_questions,
                'assumptions' => $this->assumptions,
                'risk_level' => $this->risk_level,
            ],
        ];
    }
}
