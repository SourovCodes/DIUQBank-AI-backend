<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function hasDeletionDependencies(): bool
    {
        return $this->questions()->exists();
    }

    public function scopeHasDeletionDependencies(Builder $query): Builder
    {
        return $query->whereHas('questions');
    }
}
