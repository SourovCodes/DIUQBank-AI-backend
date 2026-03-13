<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it returns paginated questions filtered by the provided filters', function () {
    $department = Department::factory()->create([
        'name' => 'Computer Science and Engineering',
        'short_name' => 'CSE',
    ]);
    $otherDepartment = Department::factory()->create();

    $course = Course::factory()->create([
        'department_id' => $department->id,
        'name' => 'Operating Systems',
    ]);
    $otherCourse = Course::factory()->create([
        'department_id' => $otherDepartment->id,
    ]);

    $semester = Semester::factory()->create(['name' => 'Semester 5']);
    $otherSemester = Semester::factory()->create();

    $examType = ExamType::factory()->create(['name' => 'Midterm']);
    $otherExamType = ExamType::factory()->create();

    $user = User::factory()->create(['name' => 'Target Uploader']);
    $otherUser = User::factory()->create();

    $matchingQuestion = Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
        'views' => 12,
    ]);

    Submission::factory()->create([
        'question_id' => $matchingQuestion->id,
        'user_id' => $user->id,
    ]);

    $nonMatchingQuestion = Question::factory()->create([
        'department_id' => $otherDepartment->id,
        'course_id' => $otherCourse->id,
        'semester_id' => $otherSemester->id,
        'exam_type_id' => $otherExamType->id,
    ]);

    Submission::factory()->create([
        'question_id' => $nonMatchingQuestion->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->getJson('/api/v1/questions?department_id='.$department->id.'&course_id='.$course->id.'&semester_id='.$semester->id.'&exam_type_id='.$examType->id.'&user_id='.$user->id.'&per_page=5');

    $response
        ->assertSuccessful()
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingQuestion->id)
        ->assertJsonPath('data.0.views', 12)
        ->assertJsonPath('data.0.department.id', $department->id)
        ->assertJsonPath('data.0.course.id', $course->id)
        ->assertJsonPath('data.0.semester.id', $semester->id)
        ->assertJsonPath('data.0.exam_type.id', $examType->id)
        ->assertJsonPath('data.0.submissions_count', 1)
        ->assertJsonMissing(['id' => $nonMatchingQuestion->id]);
});

test('it returns a question with its submissions on show', function () {
    $department = Department::factory()->create([
        'name' => 'Computer Science and Engineering',
        'short_name' => 'CSE',
    ]);
    $course = Course::factory()->create([
        'department_id' => $department->id,
        'name' => 'Database Systems',
    ]);
    $semester = Semester::factory()->create(['name' => 'Semester 6']);
    $examType = ExamType::factory()->create(['name' => 'Final']);

    $question = Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
        'views' => 24,
    ]);

    $uploader = User::factory()->create([
        'name' => 'Alice Uploader',
        'username' => 'aliceuploader',
        'avatar' => 'https://example.com/alice.png',
    ]);

    $submission = Submission::factory()->create([
        'question_id' => $question->id,
        'user_id' => $uploader->id,
        'section' => 'A',
        'batch' => '56',
        'pdf_path' => 'submissions/database-systems-final.pdf',
        'views' => 7,
    ]);

    $response = $this->getJson('/api/v1/questions/'.$question->id);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $question->id)
        ->assertJsonPath('data.department.short_name', 'CSE')
        ->assertJsonPath('data.course.name', 'Database Systems')
        ->assertJsonPath('data.semester.name', 'Semester 6')
        ->assertJsonPath('data.exam_type.name', 'Final')
        ->assertJsonPath('data.submissions_count', 1)
        ->assertJsonPath('data.submissions.0.id', $submission->id)
        ->assertJsonPath('data.submissions.0.section', 'A')
        ->assertJsonPath('data.submissions.0.batch', '56')
        ->assertJsonPath('data.submissions.0.views', 7)
        ->assertJsonPath('data.submissions.0.uploader.id', $uploader->id)
        ->assertJsonPath('data.submissions.0.uploader.username', 'aliceuploader')
        ->assertJsonPath('data.submissions.0.uploader.name', 'Alice Uploader')
        ->assertJsonPath('data.submissions.0.uploader.avatar', 'https://example.com/alice.png')
        ->assertJsonMissingPath('data.submissions.0.question_id')
        ->assertJsonMissingPath('data.submissions.0.pdf_path');
});

test('it increments a questions view count', function () {
    $question = Question::factory()->create([
        'views' => 3,
    ]);

    $response = $this->postJson('/api/v1/questions/'.$question->id.'/views');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $question->id)
        ->assertJsonPath('data.views', 4);

    expect($question->fresh()->views)->toBe(4);
});

test('it increments a submissions view count', function () {
    $submission = Submission::factory()->create([
        'views' => 9,
    ]);

    $response = $this->postJson('/api/v1/submissions/'.$submission->id.'/views');

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $submission->id)
        ->assertJsonPath('data.question_id', $submission->question_id)
        ->assertJsonPath('data.views', 10);

    expect($submission->fresh()->views)->toBe(10);
});

test('it validates question filters', function () {
    $response = $this->getJson('/api/v1/questions?department_id=999999&per_page=0');

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['department_id', 'per_page']);
});
