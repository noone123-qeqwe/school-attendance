<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GuestExcuseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private User $parent;
    private Subject $subject;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Elena Ramos',
            'email' => 'teacher.ramos@example.com',
        ]);

        $this->subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Computer Science I',
            'year_level' => 1,
            'semester' => 1,
            'instructor_id' => $this->teacher->id,
            'units' => 3,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Juan Dela Cruz',
            'student_number' => '2026-00101',
            'email' => 'juan.student@example.com',
            'course' => 'BSCS',
            'section' => '1A',
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'name' => 'Maria Dela Cruz',
            'email' => 'maria.parent@example.com',
        ]);

        $this->parent->children()->attach($this->student->id);

        $this->attendance = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
    }

    public function test_unsigned_request_to_guest_excuse_form_is_forbidden()
    {
        $response = $this->get('/excuse/' . $this->attendance->id . '/submit');
        $response->assertStatus(403);
    }

    public function test_valid_signed_url_renders_guest_excuse_form_with_student_and_class_details()
    {
        $signedUrl = URL::signedRoute('guest.excuse', ['attendance' => $this->attendance->id]);

        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertSee('Submit Excuse Letter');
        $response->assertSee($this->student->name);
        $response->assertSee($this->student->student_number);
        $response->assertSee($this->subject->code);
        $response->assertSee($this->teacher->name);
        $response->assertSee('Absent');
        $response->assertSee('dropzone');
    }

    public function test_parent_can_submit_excuse_with_attachment_and_category()
    {
        $signedStoreUrl = URL::signedRoute('guest.excuse.store', ['attendance' => $this->attendance->id]);
        $file = UploadedFile::fake()->create('medical_cert.pdf', 500, 'application/pdf');

        $response = $this->post($signedStoreUrl, [
            'reason_category' => 'Medical Illness',
            'reason' => 'Student suffered high fever and acute bronchitis diagnosed by Dr. Santos.',
            'parent_name' => 'Maria Dela Cruz',
            'parent_phone' => '0917-888-9999',
            'attachment' => $file,
        ]);

        $response->assertOk();
        $response->assertSee('Excuse Submitted');
        $response->assertSee('Pending Teacher Review');

        $this->assertDatabaseHas('excuse_submissions', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
        ]);

        $submission = ExcuseSubmission::where('attendance_id', $this->attendance->id)->first();
        $this->assertNotNull($submission);
        $this->assertStringContainsString('[Medical Illness]', $submission->reason);
        $this->assertStringContainsString('Maria Dela Cruz', $submission->description);
        $this->assertStringContainsString('0917-888-9999', $submission->description);
        $this->assertNotEmpty($submission->attachments);

        // Teacher received in-app notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->teacher->id,
            'type' => 'excuse_submitted',
            'subject_code' => $this->subject->code,
        ]);
    }

    public function test_duplicate_submission_is_prevented_and_shows_existing_status()
    {
        ExcuseSubmission::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->student->id,
            'reason' => 'Prior excuse already submitted',
            'description' => 'Original details',
            'status' => 'pending',
        ]);

        $signedUrl = URL::signedRoute('guest.excuse', ['attendance' => $this->attendance->id]);
        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertSee('Excuse Notice');
        $response->assertSee('already been submitted');

        $signedStoreUrl = URL::signedRoute('guest.excuse.store', ['attendance' => $this->attendance->id]);
        $storeResponse = $this->post($signedStoreUrl, [
            'reason' => 'Second excuse attempt should be blocked',
        ]);

        $storeResponse->assertOk();
        $storeResponse->assertSee('Excuse Notice');
    }
}
