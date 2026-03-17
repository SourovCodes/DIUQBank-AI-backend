<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'short_name',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function hasDeletionDependencies(): bool
    {
        return $this->courses()->exists() || $this->questions()->exists();
    }

    public function getDeletionDependencyMessage(): string
    {
        return 'Delete the courses and questions under this department first.';
    }

    public function scopeHasDeletionDependencies(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereHas('courses')
                ->orWhereHas('questions');
        });
    }
}
