<?php

use App\Enums\QuickUploadStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuickUpload;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects guests to the admin login page', function (): void {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('renders the admin registration page for guests', function (): void {
    $this->get('/admin/register')
        ->assertSuccessful();
});

it('registers an allowlisted admin user through filament', function (): void {
    config()->set('filament-admin.emails', ['new-admin@example.com']);

    Filament::setCurrentPanel('admin');

    Livewire::test(\App\Filament\Auth\Register::class)
        ->set('data.name', 'New Admin')
        ->set('data.username', 'new_admin')
        ->set('data.email', 'new-admin@example.com')
        ->set('data.password', 'password')
        ->set('data.passwordConfirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    $user = User::query()->where('email', 'new-admin@example.com')->sole();

    expect($user->username)->toBe('new_admin')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(auth()->id())->toBe($user->id);
});

it('forbids authenticated users who are not allowlisted', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('forbids allowlisted users whose email is not verified', function (): void {
    $user = User::factory()->unverified()->create();

    config()->set('filament-admin.emails', [$user->email]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('renders the main admin resources for an allowlisted admin', function (): void {
    Storage::fake('s3');

    $admin = User::factory()->create();

    config()->set('filament-admin.emails', [$admin->email]);

    $department = Department::factory()->create();
    $course = Course::factory()->create([
        'department_id' => $department->id,
    ]);
    $semester = Semester::factory()->create();
    $examType = ExamType::factory()->create();

    $question = Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
    ]);

    $submission = Submission::factory()->create([
        'user_id' => $admin->id,
        'question_id' => $question->id,
    ]);

    $quickUpload = QuickUpload::factory()->create([
        'user_id' => $admin->id,
        'status' => QuickUploadStatus::ManualReviewRequested,
        'manual_review_requested_at' => now(),
    ]);

    $this->actingAs($admin);

    $this->get('/admin')->assertSuccessful();
    $this->get('/admin/departments')->assertSuccessful();
    $this->get('/admin/questions')->assertSuccessful();
    $this->get('/admin/submissions/'.$submission->id.'/edit')->assertSuccessful();
    $this->get('/admin/quick-uploads/'.$quickUpload->id.'/edit')->assertSuccessful();
    $this->get('/admin/users/'.$admin->id.'/edit')->assertSuccessful();
});

it('does not expose resource view pages anymore', function (): void {
    $admin = User::factory()->create();

    config()->set('filament-admin.emails', [$admin->email]);

    $department = Department::factory()->create();
    $course = Course::factory()->create([
        'department_id' => $department->id,
    ]);
    $semester = Semester::factory()->create();
    $examType = ExamType::factory()->create();
    $question = Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
    ]);
    $submission = Submission::factory()->create([
        'user_id' => $admin->id,
        'question_id' => $question->id,
    ]);
    $quickUpload = QuickUpload::factory()->create([
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin);

    $this->get('/admin/departments/'.$department->id)->assertNotFound();
    $this->get('/admin/courses/'.$course->id)->assertNotFound();
    $this->get('/admin/semesters/'.$semester->id)->assertNotFound();
    $this->get('/admin/exam-types/'.$examType->id)->assertNotFound();
    $this->get('/admin/questions/'.$question->id)->assertNotFound();
    $this->get('/admin/submissions/'.$submission->id)->assertNotFound();
    $this->get('/admin/quick-uploads/'.$quickUpload->id)->assertNotFound();
    $this->get('/admin/users/'.$admin->id)->assertNotFound();
});

it('reports deletion dependencies for parent records', function (): void {
    $department = Department::factory()->create();
    $course = Course::factory()->create([
        'department_id' => $department->id,
    ]);
    $semester = Semester::factory()->create();
    $examType = ExamType::factory()->create();
    $question = Question::factory()->create([
        'department_id' => $department->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'exam_type_id' => $examType->id,
    ]);
    $user = User::factory()->create();

    Submission::factory()->create([
        'user_id' => $user->id,
        'question_id' => $question->id,
    ]);

    QuickUpload::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($department->hasDeletionDependencies())->toBeTrue()
        ->and($department->getDeletionDependencyMessage())->toBe('Delete the courses and questions under this department first.')
        ->and($course->hasDeletionDependencies())->toBeTrue()
        ->and($course->getDeletionDependencyMessage())->toBe('Delete the questions under this course first.')
        ->and($semester->hasDeletionDependencies())->toBeTrue()
        ->and($semester->getDeletionDependencyMessage())->toBe('Delete the questions assigned to this semester first.')
        ->and($examType->hasDeletionDependencies())->toBeTrue()
        ->and($examType->getDeletionDependencyMessage())->toBe('Delete the questions using this exam type first.')
        ->and($question->hasDeletionDependencies())->toBeTrue()
        ->and($question->getDeletionDependencyMessage())->toBe('Delete the submissions attached to this question first.')
        ->and($user->hasDeletionDependencies())->toBeTrue()
        ->and($user->getDeletionDependencyMessage())->toBe('Delete the user\'s submissions and quick uploads first.');
});
