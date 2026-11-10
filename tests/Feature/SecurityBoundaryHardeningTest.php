<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityBoundaryHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_mark_all_read_only_changes_the_teachers_own_notification_rows(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);

        $teacherAlert = $this->notification($teacher, null, 'Teacher inbox item');
        $studentAlert = $this->notification($student, $teacher->id, 'Student alert');
        $studentAlert->update(['subject_code' => 'CS101']);

        $this->actingAs($teacher)->postJson(route('teacher.notifications.read'))->assertOk();

        $this->assertTrue($teacherAlert->fresh()->is_read);
        $this->assertFalse($studentAlert->fresh()->is_read);
    }

    public function test_teacher_cannot_archive_unarchive_or_delete_a_students_notification(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $studentAlert = $this->notification($student, $teacher->id, 'Student alert');
        $studentAlert->update(['subject_code' => $subject->code]);

        $this->actingAs($teacher)
            ->postJson(route('teacher.notifications.archive', $studentAlert))->assertForbidden();
        $this->postJson(route('teacher.notifications.unarchive', $studentAlert))->assertForbidden();
        $this->deleteJson(route('teacher.notifications.delete', $studentAlert))->assertForbidden();

        $this->assertNull($studentAlert->fresh()->archived_at);
        $this->assertDatabaseHas('notifications', ['id' => $studentAlert->id]);
    }

    public function test_student_excuse_request_does_not_create_a_missing_absence_record(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'year_level' => 2, 'semester' => 1,
            'course' => 'BSCS', 'section' => 'A',
        ]);
        $subject = Subject::factory()->create([
            'code' => 'EXCUSE101', 'year_level' => 2, 'semester' => 1,
            'course' => 'BSCS', 'section' => 'A',
        ]);
        $date = today()->subDay()->toDateString();

        $this->actingAs($student)->post(route('excuses.store_general'), [
            'subject_code' => $subject->code,
            'date' => $date,
            'reason' => 'Medical issue',
            'description' => 'Supporting details for the absence.',
        ])->assertRedirect();

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id, 'subject_code' => $subject->code, 'date' => $date,
        ]);
        $this->assertDatabaseMissing('excuse_submissions', ['user_id' => $student->id]);
    }

    public function test_student_cannot_submit_an_excuse_for_a_future_date(): void
    {
        $student = User::factory()->create([
            'role' => 'student', 'year_level' => 2, 'semester' => 1,
            'course' => 'BSCS', 'section' => 'A',
        ]);
        $subject = Subject::factory()->create([
            'code' => 'EXCUSE102', 'year_level' => 2, 'semester' => 1,
            'course' => 'BSCS', 'section' => 'A',
        ]);
        $date = today()->addDay()->toDateString();

        $this->actingAs($student)->from(route('excuses.create_general'))
            ->post(route('excuses.store_general'), [
                'subject_code' => $subject->code,
                'date' => $date,
                'reason' => 'Medical issue',
                'description' => 'Supporting details for the absence.',
            ])->assertSessionHasErrors('date');

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id, 'subject_code' => $subject->code, 'date' => $date,
        ]);
    }

    public function test_excuse_attachment_requires_authenticated_ownership_or_class_authorization(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('excuses/medical.pdf', 'private medical evidence');

        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => today()->subDay()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        $excuse = ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $student->id,
            'reason' => 'Medical issue',
            'description' => 'Private document',
            'attachments' => ['excuses/medical.pdf'],
            'status' => 'pending',
        ]);

        $uri = route('excuse-attachments.show', [$excuse->id, 0]);
        $this->actingAs($otherTeacher)->get($uri)->assertForbidden();
        $this->actingAs($admin)->withSession(['admin_2fa_verified' => false])->get($uri)->assertForbidden();
        $adminResponse = $this->actingAs($admin)->withSession(['admin_2fa_verified' => true])->get($uri)->assertOk();
        $this->assertSame('private medical evidence', $adminResponse->streamedContent());
        $authorized = $this->actingAs($teacher)->get($uri)->assertOk();
        $this->assertSame('private medical evidence', $authorized->streamedContent());
        $this->get('/storage/excuse_attachments/medical.pdf')->assertNotFound();
    }

    public function test_linked_parent_and_student_can_access_their_excuse_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('excuses/parent.pdf', 'authorized attachment');

        $student = User::factory()->create(['role' => 'student']);
        $parent = User::factory()->create(['role' => 'parent']);
        $parent->children()->attach($student->id);
        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'CS101',
            'date' => today()->subDay()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        $excuse = ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $student->id,
            'reason' => 'Medical issue',
            'description' => 'Private document',
            'attachments' => ['excuses/parent.pdf'],
            'status' => 'pending',
        ]);
        $uri = route('excuse-attachments.show', [$excuse->id, 0]);

        $studentResponse = $this->actingAs($student)->get($uri)->assertOk();
        $this->assertSame('authorized attachment', $studentResponse->streamedContent());
        $parentResponse = $this->actingAs($parent)->get($uri)->assertOk();
        $this->assertSame('authorized attachment', $parentResponse->streamedContent());
    }

    public function test_legacy_local_public_attachment_is_moved_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('excuse_attachments/legacy.pdf', 'legacy private evidence');

        $student = User::factory()->create(['role' => 'student']);
        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'LEGACY101',
            'date' => today()->subDay()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        $excuse = ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $student->id,
            'reason' => 'Medical issue',
            'description' => 'Legacy attachment',
            'attachments' => ['excuse_attachments/legacy.pdf'],
            'status' => 'pending',
        ]);

        $this->artisan('excuses:privatize-attachments', ['--apply' => true])->assertExitCode(0);

        $privatePath = $excuse->fresh()->attachments[0];
        $this->assertStringStartsWith('excuses/', $privatePath);
        Storage::disk('local')->assertExists($privatePath);
        Storage::disk('public')->assertMissing('excuse_attachments/legacy.pdf');
    }

    private function notification(User $recipient, ?int $senderId, string $message): Notification
    {
        return Notification::create([
            'user_id' => $recipient->id,
            'sent_by' => $senderId,
            'type' => 'custom',
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
