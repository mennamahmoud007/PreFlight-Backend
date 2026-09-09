<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'problem_score',
        'target_score',
        'value_score',
        'feasibility_score',
        'differentiation_score',
        'overall_score',
        'summary',
        'strengths',
        'weaknesses',
        'risks',
        'critical_questions',
        'primary_concern',
        'assumptions',
        'risk_level',
    ];

    // when i will use the model to create a new analysis,
    //  i will be able to pass an array of strengths, weaknesses, risks, critical questions, and assumptions
    // and it will be automatically casted to an array when i retrieve it from the database
    protected $casts = [
        'strengths' => 'array',
        'weaknesses' => 'array',
        'risks' => 'array',
        'critical_questions' => 'array',
        'assumptions' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
