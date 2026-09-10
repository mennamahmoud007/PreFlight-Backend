<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'name',
        'description',
        'target_audience',
        'industry',
        'status',
        'score',
        'last_checked_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'last_checked_at' => 'datetime',
    ];

    public function analysis(): HasOne
    {
        return $this->hasOne(Analysis::class);
    }

    public function pitchSections(): HasMany
    {
        return $this->hasMany(PitchSection::class);
    }

    public function improvements(): HasMany
    {
        return $this->hasMany(Improvement::class);
    }
}
