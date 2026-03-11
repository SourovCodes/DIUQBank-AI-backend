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

test('it returns paginated contributors ordered by submissions count', function () {
    $department = Department::factory()->create([
        'name' => 'Computer Science and Engineering',
        'short_name' => 'CSE',
    ]);
    $course = Course::factory()->create([
        'department_id' => $department->id,
        'name' => 'Algorithms',
    ]);
    $semester = Semester::factory()->create(['name' => 'Semester 4']);
    $examType = ExamType::factory()->create(['name' => 'Quiz']);

    $question = Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
    ]);

    $topContributor = User::factory()->create([
        'name' => 'Alice',
        'username' => 'alice',
        'avatar' => 'avatars/alice.png',
    ]);
    $secondContributor = User::factory()->create([
        'name' => 'Bob',
        'username' => 'bob',
    ]);
    $nonContributor = User::factory()->create([
        'name' => 'Charlie',
        'username' => 'charlie',
    ]);

    Submission::factory()->count(2)->create([
        'user_id' => $topContributor->id,
        'question_id' => $question->id,
        'views' => 3,
    ]);

    Submission::factory()->create([
        'user_id' => $secondContributor->id,
        'question_id' => $question->id,
        'views' => 5,
    ]);

    $response = $this->getJson('/api/contributors?per_page=10');

    $response
        ->assertSuccessful()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $topContributor->id)
        ->assertJsonPath('data.0.name', 'Alice')
        ->assertJsonPath('data.0.username', 'alice')
        ->assertJsonPath('data.0.avatar', 'avatars/alice.png')
        ->assertJsonPath('data.0.submissions_count', 2)
        ->assertJsonPath('data.0.submission_views_sum', 6)
        ->assertJsonPath('data.1.id', $secondContributor->id)
        ->assertJsonPath('data.1.username', 'bob')
        ->assertJsonPath('data.1.avatar', null)
        ->assertJsonPath('data.1.submissions_count', 1)
        ->assertJsonMissing(['id' => $nonContributor->id, 'name' => 'Charlie'])
        ->assertJsonMissingPath('data.0.email');
});

test('it returns a contributor summary without submissions', function () {
    $department = Department::factory()->create([
        'name' => 'Computer Science and Engineering',
        'short_name' => 'CSE',
    ]);
    $course = Course::factory()->create([
        'department_id' => $department->id,
        'name' => 'Computer Networks',
    ]);
    $semester = Semester::factory()->create(['name' => 'Semester 7']);
    $examType = ExamType::factory()->create(['name' => 'Final']);

    $question = Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
        'views' => 15,
    ]);

    $contributor = User::factory()->create([
        'name' => 'Diana',
        'username' => 'diana',
        'avatar' => 'avatars/diana.png',
    ]);

    Submission::factory()->create([
        'user_id' => $contributor->id,
        'question_id' => $question->id,
        'section' => 'B',
        'batch' => '58',
        'views' => 11,
        'pdf_path' => 'submissions/networks-final.pdf',
    ]);

    $response = $this->getJson('/api/contributors/'.$contributor->id);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $contributor->id)
        ->assertJsonPath('data.name', 'Diana')
        ->assertJsonPath('data.username', 'diana')
        ->assertJsonPath('data.avatar', 'avatars/diana.png')
        ->assertJsonPath('data.submissions_count', 1)
        ->assertJsonPath('data.submission_views_sum', 11)
        ->assertJsonMissingPath('data.submissions')
        ->assertJsonMissingPath('data.questions');
});

test('it returns not found for a user without submissions', function () {
    $user = User::factory()->create();

    $this->getJson('/api/contributors/'.$user->id)->assertNotFound();
});

test('it validates contributor pagination input', function () {
    $response = $this->getJson('/api/contributors?per_page=0');

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});
