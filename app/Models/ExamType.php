<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'requires_section',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_section' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function hasDeletionDependencies(): bool
    {
        return $this->questions()->exists();
    }

    public function getDeletionDependencyMessage(): string
    {
        return 'Delete the questions using this exam type first.';
    }

    public function scopeHasDeletionDependencies(Builder $query): Builder
    {
        return $query->whereHas('questions');
    }
}
