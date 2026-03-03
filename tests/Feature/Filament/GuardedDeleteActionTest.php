<?php

use App\Filament\Resources\Semesters\Pages\EditSemester;
use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Semester;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('prevents deleting a semester when it has related questions', function (): void {
    $semester = Semester::factory()->create();
    $department = Department::factory()->create();
    $course = Course::factory()->create([
        'department_id' => $department->id,
    ]);
    $examType = ExamType::factory()->create();

    Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
    ]);

    Livewire::test(EditSemester::class, [
        'record' => $semester->getKey(),
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified();

    assertDatabaseHas(Semester::class, [
        'id' => $semester->id,
    ]);
});

it('allows deleting a semester when it has no related questions', function (): void {
    $semester = Semester::factory()->create();

    Livewire::test(EditSemester::class, [
        'record' => $semester->getKey(),
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified();

    assertDatabaseMissing(Semester::class, [
        'id' => $semester->id,
    ]);
});
