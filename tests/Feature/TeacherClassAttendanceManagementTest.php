<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherClassAttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role, array $overrides = []): User
    {
        static $seq = 0;
        $seq++;

        return User::create(array_merge([
            'name' => ucfirst($role) . ' Class Mgmt ' . $seq,
            'student_number' => $role === 'student' ? '2026-CLS-' . $seq : null,
            'employee_id' => $role === 'teacher' ? 'FAC-CLS-' . $seq : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '3',
            'semester' => '1',
            'section' => $role === 'student' ? 'A' : null,
            'email' => $role . '.clsmgmt.' . $seq . '@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    public function test_teacher_navigation_removes_standalone_attendance_records_and_preserves_class_card_actions(): void
    {
        $teacher = $this->createUser('teacher');
        $subject = Subject::create([
            'code' => 'CS411',
            'name' => 'Applied AI',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);

        $dashboardHtml = $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk()->getContent();
        preg_match('/<nav class="sidebar-nav teacher-sidebar-nav".*?<\/nav>/s', $dashboardHtml, $sidebarMatches);
        $sidebarHtml = $sidebarMatches[0] ?? '';
        $this->assertNotEmpty($sidebarHtml);
        $this->assertStringNotContainsString('Attendance Records', $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.attendance'), $sidebarHtml);

        preg_match('/<nav class="mobile-bottom-nav.*?<\/nav>/s', $dashboardHtml, $mbnMatches);
        $mbnHtml = $mbnMatches[0] ?? '';
        $this->assertNotEmpty($mbnHtml);
        $this->assertStringNotContainsString(route('teacher.attendance'), $mbnHtml);
        $this->assertStringContainsString(route('teacher.calendar'), $mbnHtml);

        $classesHtml = $this->actingAs($teacher)->get(route('teacher.classroom.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Open class', $classesHtml);
        $this->assertStringContainsString('Take attendance', $classesHtml);
        $this->assertStringContainsString(route('teacher.classroom.show', $subject->code), $classesHtml);
        $this->assertStringContainsString(route('teacher.qr', $subject->code), $classesHtml);
    }

    public function test_class_workspace_renders_four_tabs_and_isolates_records_sessions_and_exports_per_class(): void
    {
        $teacher = $this->createUser('teacher');
        $otherTeacher = $this->createUser('teacher');

        $studentA = $this->createUser('student', ['name' => 'Alice Alpha', 'student_number' => '2026-0001']);
        $studentB = $this->createUser('student', ['name' => 'Bob Beta', 'student_number' => '2026-0002']);

        $subjectOne = Subject::create([
            'code' => 'CS401',
            'name' => 'Distributed Systems',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);
        $subjectTwo = Subject::create([
            'code' => 'CS402',
            'name' => 'Compiler Design',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);
        $otherSubject = Subject::create([
            'code' => 'CS999',
            'name' => 'Foreign Teacher Subject',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $otherTeacher->id,
        ]);

        $sessionOne = AttendanceSession::create([
            'subject_code' => $subjectOne->code,
            'created_by' => $teacher->id,
            'token' => 'tok-1',
            'session_code' => 'SESS01',
            'expires_at' => now()->addMinute(),
            'session_ends_at' => now()->addHour(),
            'active' => true,
        ]);

        $attOne = Attendance::create([
            'user_id' => $studentA->id,
            'subject_id' => $subjectOne->id,
            'subject_code' => $subjectOne->code,
            'subject_name' => $subjectOne->name,
            'session_id' => $sessionOne->id,
            'date' => '2026-11-20',
            'status' => 'Present',
            'excused' => false,
            'time_in' => '09:05:00',
            'method' => 'qr',
        ]);
        $attOneLate = Attendance::create([
            'user_id' => $studentB->id,
            'subject_id' => $subjectOne->id,
            'subject_code' => $subjectOne->code,
            'subject_name' => $subjectOne->name,
            'session_id' => null,
            'date' => '2026-11-21',
            'status' => 'Late',
            'excused' => false,
            'method' => 'manual',
        ]);
        $attTwo = Attendance::create([
            'user_id' => $studentB->id,
            'subject_id' => $subjectTwo->id,
            'subject_code' => $subjectTwo->code,
            'subject_name' => $subjectTwo->name,
            'date' => '2026-11-20',
            'status' => 'Absent',
            'excused' => false,
            'method' => 'manual',
        ]);
        $foreignAtt = Attendance::create([
            'user_id' => $studentA->id,
            'subject_id' => $otherSubject->id,
            'subject_code' => $otherSubject->code,
            'subject_name' => $otherSubject->name,
            'date' => '2026-11-20',
            'status' => 'Absent',
            'excused' => false,
        ]);

        // Class 1 page has all 4 tabs and strictly only Class 1 records
        $htmlOne = $this->actingAs($teacher)
            ->get(route('teacher.classroom.show', ['subjectCode' => $subjectOne->code, 'tab' => 'records']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="tab-btn-overview"', $htmlOne);
        $this->assertStringContainsString('id="tab-btn-roster"', $htmlOne);
        $this->assertStringContainsString('id="tab-btn-records"', $htmlOne);
        $this->assertStringContainsString('id="tab-btn-sessions"', $htmlOne);
        $this->assertStringContainsString('SESS01', $htmlOne);
        $this->assertStringContainsString('Alice Alpha', $htmlOne);
        $this->assertStringContainsString('Bob Beta', $htmlOne);
        $this->assertStringNotContainsString($otherSubject->code, $htmlOne);
        $this->assertStringNotContainsString('Foreign Teacher Subject', $htmlOne);

        // Class 2 strictly excludes Class 1 session & records (no Alice Alpha check-in for CS402)
        $htmlTwo = $this->actingAs($teacher)
            ->get(route('teacher.classroom.show', ['subjectCode' => $subjectTwo->code, 'tab' => 'records']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('SESS01', $htmlTwo);
        $this->assertStringContainsString('Bob Beta', $htmlTwo);
        $this->assertStringNotContainsString('data-record-row="' . $attOne->id . '"', $htmlTwo);

        // Filter by session_id, status, student_id, date, and search within Class 1
        $sessionFiltered = $this->actingAs($teacher)->get(route('teacher.classroom.show', [
            'subjectCode' => $subjectOne->code,
            'tab' => 'records',
            'session_id' => $sessionOne->id,
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('data-record-row="' . $attOne->id . '"', $sessionFiltered);
        $this->assertStringNotContainsString('data-record-row="' . $attOneLate->id . '"', $sessionFiltered);

        $manualFiltered = $this->actingAs($teacher)->get(route('teacher.classroom.show', [
            'subjectCode' => $subjectOne->code,
            'tab' => 'records',
            'session_id' => 'manual',
            'status' => 'Late',
            'student_id' => $studentB->id,
            'date' => '2026-11-21',
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('data-record-row="' . $attOneLate->id . '"', $manualFiltered);
        $this->assertStringNotContainsString('data-record-row="' . $attOne->id . '"', $manualFiltered);

        // Authorized correction works & stores excuse note
        $this->actingAs($teacher)->postJson(route('teacher.attendance.override', $attOneLate), [
            'status' => 'Excused',
            'reason' => 'Official hackathon rep',
            'excuse_note' => 'Verified dean letter',
        ])->assertOk()->assertJson([
            'success' => true,
            'status' => 'Excused',
            'excused' => true,
            'excuse_note' => 'Verified dean letter',
        ]);
        $this->assertTrue((bool) $attOneLate->fresh()->excused);
        $this->assertSame('Verified dean letter', $attOneLate->fresh()->excuse_note);

        // Excused filter finds the updated record
        $excusedFiltered = $this->actingAs($teacher)->get(route('teacher.classroom.show', [
            'subjectCode' => $subjectOne->code,
            'tab' => 'records',
            'status' => 'Excused',
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('data-record-row="' . $attOneLate->id . '"', $excusedFiltered);
        $this->assertStringNotContainsString('data-record-row="' . $attOne->id . '"', $excusedFiltered);

        // Class-scoped exports (Preview PDF, PDF, CSV, Excel) succeed
        $exportParams = ['subject' => $subjectOne->code, 'subject_id' => $subjectOne->id];
        $this->actingAs($teacher)->get(route('teacher.attendance.preview', $exportParams))->assertOk()
            ->assertSee('CS401')
            ->assertSee('Back to CS401 Records');
        $this->actingAs($teacher)->get(route('teacher.attendance.pdf', $exportParams))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.attendance.csv', $exportParams))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.attendance.xlsx', $exportParams))->assertOk();

        // Authorization boundaries enforced on backend
        $this->actingAs($teacher)->get(route('teacher.classroom.show', $otherSubject->code))->assertNotFound();
        $this->actingAs($teacher)->get(route('teacher.attendance.preview', ['subject' => $otherSubject->code]))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.attendance.pdf', ['subject_id' => $otherSubject->id]))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.attendance.csv', ['subject_code' => $otherSubject->code]))->assertForbidden();
        $this->actingAs($teacher)->get(route('teacher.attendance.xlsx', ['subject' => $otherSubject->code]))->assertForbidden();
        $this->actingAs($teacher)->postJson(route('teacher.attendance.override', $foreignAtt), ['status' => 'Present'])->assertForbidden();
        $this->actingAs($teacher)->post(route('teacher.attendance.excuse', $foreignAtt))->assertForbidden();

        // Legacy URL redirects seamlessly into class records
        $this->actingAs($teacher)->get(route('teacher.attendance', ['subject' => $subjectOne->code, 'date' => '2026-11-20']))
            ->assertRedirect(route('teacher.classroom.show', ['subjectCode' => $subjectOne->code, 'tab' => 'records', 'date' => '2026-11-20']));
        $this->actingAs($teacher)->get(route('teacher.attendance', ['subject' => $otherSubject->code]))->assertForbidden();
    }

    public function test_newly_created_class_automatically_receives_attendance_records_and_sessions_management(): void
    {
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student', ['name' => 'Clara Newclass', 'year_level' => '2', 'semester' => '1', 'course' => 'BSIT', 'section' => 'B']);

        $this->actingAs($teacher)->post(route('teacher.subjects.store'), [
            'code' => 'IT205',
            'name' => 'Mobile Engineering',
            'year_level' => 2,
            'semester' => 1,
            'section' => 'B',
            'days' => 'Monday, Wednesday',
            'start_time' => '10:00',
            'end_time' => '11:30',
        ])->assertRedirect(route('teacher.classroom.index'));

        $created = Subject::where('code', 'IT205')->firstOrFail();

        $this->actingAs($teacher)->get(route('teacher.classroom.show', $created->code))
            ->assertOk()
            ->assertSee('id="tab-btn-overview"', false)
            ->assertSee('id="tab-btn-roster"', false)
            ->assertSee('id="tab-btn-records"', false)
            ->assertSee('id="tab-btn-sessions"', false)
            ->assertSee('Clara Newclass');

        $this->actingAs($teacher)->post(route('teacher.classroom.attendance.store', $created->code), [
            'date' => '2026-11-27',
            'attendance' => [$student->id => 'Present'],
        ])->assertRedirect(route('teacher.classroom.show', ['subjectCode' => $created->code, 'tab' => 'records', 'date' => '2026-11-27']));

        $savedRecord = Attendance::where('user_id', $student->id)
            ->where('subject_id', $created->id)
            ->firstOrFail();

        $this->actingAs($teacher)->get(route('teacher.classroom.show', ['subjectCode' => $created->code, 'tab' => 'records']))
            ->assertOk()
            ->assertSee('data-record-row="' . $savedRecord->id . '"', false)
            ->assertSee('Clara Newclass');
    }
}
