<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Department::factory()
            ->count(5)
            ->hasCourses(6)
            ->create();

        Semester::factory()->count(8)->create();
        ExamType::factory()->count(5)->create();

        $semesters = Semester::query()->pluck('id')->values();
        $examTypes = ExamType::query()->pluck('id')->values();

        Course::query()
            ->select(['id', 'department_id'])
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function (Course $course, int $index) use ($semesters, $examTypes): void {
                Question::factory()->create([
                    'department_id' => $course->department_id,
                    'course_id' => $course->id,
                    'semester_id' => $semesters[$index % $semesters->count()],
                    'exam_type_id' => $examTypes[$index % $examTypes->count()],
                ]);
            });
    }
}
