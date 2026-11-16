<?php

namespace Tests\Feature;

use App\Mail\AbsenceWarning;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\ExcuseSubmission;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentScheduleService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BugFixRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_absence_warning_mail_preserves_subject_envelope_without_mailable_property_collision(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Student One',
            'guardian_email' => 'guardian@gmail.com',
        ]);
        $subject = Subject::create([
            'code' => 'CS401',
            'name' => 'Software Engineering',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $mail = new AbsenceWarning($student, $subject, 3);
        $envelope = $mail->envelope();
        $rendered = $mail->render();

        $this->assertStringContainsString('Software Engineering', $envelope->subject);
        $this->assertStringContainsString('Software Engineering', $rendered);
        $this->assertStringContainsString('CS401', $rendered);
        $this->assertStringContainsString('Accumulated Absences: 3', $rendered);
    }

    public function test_legacy_cloudinary_excuse_attachment_falls_back_to_octet_stream_when_content_type_missing(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['code' => 'CS205']);
        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => '2026-09-10',
            'status' => 'Absent',
        ]);
        $excuse = ExcuseSubmission::create([
            'user_id' => $student->id,
            'attendance_id' => $attendance->id,
            'reason' => 'Medical',
            'description' => 'Clinic slip',
            'attachments' => ['https://res.cloudinary.com/demo/image/upload/v1/slip.pdf'],
            'status' => 'pending',
        ]);

        Http::fake([
            'https://res.cloudinary.com/*' => Http::response('PDF-BYTES', 200, []),
        ]);

        $response = $this->actingAs($student)->get(route('excuse-attachments.show', [$excuse, 0]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertSame('PDF-BYTES', $response->getContent());
    }

    public function test_admin_can_reschedule_event_with_string_times_and_send_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $attendee = User::factory()->create(['role' => 'teacher']);
        $event = Event::create([
            'name' => 'University Convocation',
            'description' => 'All-day gathering',
            'date' => '2026-10-12',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'location' => 'Main Auditorium',
            'type' => 'school_event',
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);
        $event->attendees()->attach($attendee->id);

        $response = $this->actingAs($admin)
            ->withSession(['admin_2fa_verified' => true])
            ->putJson(route('admin.calendar.update', $event->id), [
                'date' => '2026-10-15',
                'start_time' => '09:30:00',
                'end_time' => '11:30:00',
                'location' => 'University Gym',
                'reschedule_reason' => 'Venue updated',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'location' => 'University Gym',
            'status' => 'rescheduled',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $attendee->id,
            'type' => 'calendar_reschedule',
        ]);
    }

    public function test_student_schedule_service_returns_eloquent_collection_and_preserves_explicit_enrollment_schedules(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
        ]);
        $subject = Subject::create([
            'code' => 'ELEC301',
            'name' => 'Cross-Department Elective',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSED',
            'section' => null,
        ]);
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Friday',
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
        ]);
        $student->enrolledSubjects()->attach($subject->id);

        $subjects = app(StudentScheduleService::class)->subjectsFor($student);

        $this->assertInstanceOf(EloquentCollection::class, $subjects);
        $this->assertCount(1, $subjects);
        $this->assertCount(1, $subjects->first()->schedules);
    }
}
