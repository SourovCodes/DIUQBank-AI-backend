<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'course_id',
        'semester_id',
        'exam_type_id',
        'views',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'views' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function hasDeletionDependencies(): bool
    {
        return $this->submissions()->exists();
    }

    public function getDeletionDependencyMessage(): string
    {
        return 'Delete the submissions attached to this question first.';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['department_id'] ?? null, fn (Builder $query, mixed $departmentId): Builder => $query->where('department_id', $departmentId))
            ->when($filters['course_id'] ?? null, fn (Builder $query, mixed $courseId): Builder => $query->where('course_id', $courseId))
            ->when($filters['semester_id'] ?? null, fn (Builder $query, mixed $semesterId): Builder => $query->where('semester_id', $semesterId))
            ->when($filters['exam_type_id'] ?? null, fn (Builder $query, mixed $examTypeId): Builder => $query->where('exam_type_id', $examTypeId))
            ->when($filters['user_id'] ?? null, function (Builder $query, mixed $userId): Builder {
                return $query->whereHas('submissions', fn (Builder $submissionQuery): Builder => $submissionQuery->where('user_id', $userId));
            });
    }

    public function getSubmissionDisplayLabel(): string
    {
        return sprintf(
            '%s - %s | %s | %s | Q#%d',
            $this->department?->short_name ?? 'N/A',
            $this->course?->name ?? 'N/A',
            $this->semester?->name ?? 'N/A',
            $this->examType?->name ?? 'N/A',
            $this->id,
        );
    }
}
