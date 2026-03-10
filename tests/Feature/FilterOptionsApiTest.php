<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it returns public filter options', function () {
    $department = Department::factory()->create([
        'name' => 'Computer Science and Engineering',
        'short_name' => 'CSE',
    ]);
    $unusedDepartment = Department::factory()->create([
        'name' => 'Electrical and Electronic Engineering',
        'short_name' => 'EEE',
    ]);

    $course = Course::factory()->create([
        'department_id' => $department->id,
        'name' => 'Data Structures',
    ]);
    $unusedCourse = Course::factory()->create([
        'department_id' => $unusedDepartment->id,
        'name' => 'Circuit Theory',
    ]);

    $semester = Semester::factory()->create([
        'name' => 'Semester 3',
    ]);
    $unusedSemester = Semester::factory()->create([
        'name' => 'Semester 6',
    ]);

    $examType = ExamType::factory()->create([
        'name' => 'Final',
        'requires_section' => true,
    ]);
    $unusedExamType = ExamType::factory()->create([
        'name' => 'Quiz',
        'requires_section' => false,
    ]);

    Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
    ]);

    $response = $this->getJson('/api/filter-options');

    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'departments' => [['id', 'name', 'short_name']],
            'courses' => [['id', 'department_id', 'name']],
            'semesters' => [['id', 'name']],
            'exam_types' => [['id', 'name']],
        ])
        ->assertJsonFragment([
            'id' => $department->id,
            'name' => 'Computer Science and Engineering',
            'short_name' => 'CSE',
        ])
        ->assertJsonFragment([
            'id' => $course->id,
            'department_id' => $department->id,
            'name' => 'Data Structures',
        ])
        ->assertJsonFragment([
            'id' => $semester->id,
            'name' => 'Semester 3',
        ])
        ->assertJsonFragment([
            'id' => $examType->id,
            'name' => 'Final',
        ])
        ->assertJsonMissing([
            'id' => $unusedDepartment->id,
            'name' => 'Electrical and Electronic Engineering',
            'short_name' => 'EEE',
        ])
        ->assertJsonMissing([
            'id' => $unusedCourse->id,
            'department_id' => $unusedDepartment->id,
            'name' => 'Circuit Theory',
        ])
        ->assertJsonMissing([
            'id' => $unusedSemester->id,
            'name' => 'Semester 6',
        ])
        ->assertJsonMissing([
            'id' => $unusedExamType->id,
            'name' => 'Quiz',
        ])
        ->assertJsonMissingPath('exam_types.0.requires_section');
});
