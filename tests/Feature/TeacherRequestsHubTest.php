<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\ExcuseSubmission;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherRequestsHubTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role, array $overrides = []): User
    {
        static $seq = 0;
        $seq++;

        return User::create(array_merge([
            'name' => ucfirst($role) . ' Requests ' . $seq,
            'student_number' => $role === 'student' ? '2026-REQ-' . $seq : null,
            'employee_id' => $role === 'teacher' ? 'FAC-REQ-' . $seq : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '3',
            'semester' => '1',
            'section' => $role === 'student' ? 'A' : null,
            'email' => $role . '.requests.' . $seq . '@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    private function createSubject(User $teacher, string $code = 'CS501', string $name = 'Software Verification'): Subject
    {
        return Subject::create([
            'code' => $code,
            'name' => $name,
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);
    }

    private function extractHtmlBlock(string $html, string $pattern): string
    {
        preg_match($pattern, $html, $matches);
        return $matches[0] ?? '';
    }

    public function test_sidebar_and_mobile_more_menu_combine_requests_with_summed_pending_badge(): void
    {
        $teacher = $this->createUser('teacher');
        $studentA = $this->createUser('student');
        $studentB = $this->createUser('student');
        $subject = $this->createSubject($teacher, 'CS510', 'Human Computer Interaction');

        // 2 pending student excuses
        foreach ([$studentA, $studentB] as $idx => $student) {
            $att = Attendance::create([
                'user_id' => $student->id,
                'subject_code' => $subject->code,
                'date' => now()->subDays($idx + 1)->toDateString(),
                'status' => 'Absent',
                'excused' => false,
            ]);
            ExcuseSubmission::create([
                'user_id' => $student->id,
                'attendance_id' => $att->id,
                'reason' => 'Medical appointment ' . ($idx + 1),
                'description' => 'Clinic slip attached',
                'status' => 'pending',
            ]);
        }

        // 1 pending attendance correction
        $attForCorrection = Attendance::create([
            'user_id' => $studentA->id,
            'subject_code' => $subject->code,
            'date' => now()->subDays(5)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        AttendanceCorrection::create([
            'attendance_id' => $attForCorrection->id,
            'student_id' => $studentA->id,
            'reason' => 'Arrived on time before roll call closed',
            'status' => 'pending',
        ]);

        // 1 teacher's own pending faculty excuse (must NOT inflate student review pending badge)
        $teacherOwnAttendance = Attendance::create([
            'user_id' => $teacher->id,
            'subject_code' => $subject->code,
            'date' => now()->subDays(6)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        ExcuseSubmission::create([
            'user_id' => $teacher->id,
            'attendance_id' => $teacherOwnAttendance->id,
            'reason' => 'Academic conference travel',
            'description' => 'Keynote panel invitation',
            'status' => 'pending',
        ]);

        $html = $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk()->getContent();

        $sidebarHtml = $this->extractHtmlBlock($html, '/<nav class="sidebar-nav teacher-sidebar-nav".*?<\/nav>/s');
        $this->assertNotEmpty($sidebarHtml);
        $this->assertStringContainsString(route('teacher.excuse.reviews'), $sidebarHtml);
        $this->assertStringContainsString('Excuses &amp; Corrections', $sidebarHtml);
        $this->assertStringContainsString('aria-label="3 pending requests"', $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.corrections'), $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.excuses'), $sidebarHtml);
        $this->assertStringNotContainsString('Excuse Reviews', $sidebarHtml);
        $this->assertStringNotContainsString('Correction Requests', $sidebarHtml);
        $this->assertStringNotContainsString('My Excuses', $sidebarHtml);

        $moreSheetHtml = $this->extractHtmlBlock($html, '/<div class="more-sheet-grid" id="moreSheetContent">.*?<script/s');
        $this->assertNotEmpty($moreSheetHtml);
        $this->assertStringContainsString(route('teacher.excuse.reviews'), $moreSheetHtml);
        $this->assertStringContainsString('Excuses &amp; Corrections', $moreSheetHtml);
        $this->assertStringContainsString('aria-label="3 pending requests"', $moreSheetHtml);
        $this->assertStringNotContainsString(route('teacher.corrections'), $moreSheetHtml);
        $this->assertStringNotContainsString(route('teacher.excuses'), $moreSheetHtml);
        $this->assertStringNotContainsString('My Excuse Requests', $moreSheetHtml);
    }

    public function test_all_three_routes_render_unified_hub_and_activate_matching_tab(): void
    {
        $teacher = $this->createUser('teacher');
        $this->createSubject($teacher, 'CS511', 'Operating Systems');

        $cases = [
            route('teacher.excuse.reviews') => 'tab-btn-excuses',
            route('teacher.corrections') => 'tab-btn-corrections',
            route('teacher.excuses') => 'tab-btn-my-excuses',
        ];

        foreach ($cases as $url => $expectedActiveBtnId) {
            $html = $this->actingAs($teacher)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('id="requestsHubWorkspace"', $html);
            $this->assertStringContainsString('id="tab-btn-excuses"', $html);
            $this->assertStringContainsString('id="tab-btn-corrections"', $html);
            $this->assertStringContainsString('id="tab-btn-my-excuses"', $html);

            // Desktop sidebar item stays highlighted on every tab route
            $sidebarHtml = $this->extractHtmlBlock($html, '/<nav class="sidebar-nav teacher-sidebar-nav".*?<\/nav>/s');
            $this->assertStringContainsString('aria-current="page"', $sidebarHtml);

            // Exact tab button is active with aria-selected="true"
            $this->assertMatchesRegularExpression(
                '/id="' . preg_quote($expectedActiveBtnId, '/') . '".*?aria-selected="true"/s',
                $html,
                "Expected {$expectedActiveBtnId} to be selected on {$url}"
            );
        }
    }

    public function test_hub_isolates_teacher_classes_and_separates_student_reviews_from_faculty_excuses(): void
    {
        $teacher = $this->createUser('teacher');
        $otherTeacher = $this->createUser('teacher');

        $myStudent = $this->createUser('student', ['name' => 'Elena MyClass']);
        $otherStudent = $this->createUser('student', ['name' => 'Carlos OtherClass']);

        $mySubject = $this->createSubject($teacher, 'CS520', 'Database Engineering');
        $otherSubject = $this->createSubject($otherTeacher, 'CS999', 'Foreign Seminar');

        // Student excuse for current teacher
        $myAtt = Attendance::create([
            'user_id' => $myStudent->id,
            'subject_code' => $mySubject->code,
            'date' => now()->subDays(2)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        ExcuseSubmission::create([
            'user_id' => $myStudent->id,
            'attendance_id' => $myAtt->id,
            'reason' => 'MyStudent Fever Excuse',
            'description' => 'Student note',
            'status' => 'pending',
        ]);

        // Correction request for current teacher
        $myAttCorr = Attendance::create([
            'user_id' => $myStudent->id,
            'subject_code' => $mySubject->code,
            'date' => now()->subDays(3)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        AttendanceCorrection::create([
            'attendance_id' => $myAttCorr->id,
            'student_id' => $myStudent->id,
            'reason' => 'MyClass QR scanner timeout',
            'status' => 'pending',
        ]);

        // Teacher's own faculty excuse (same subject code, user_id === teacher)
        $teacherOwnAtt = Attendance::create([
            'user_id' => $teacher->id,
            'subject_code' => $mySubject->code,
            'date' => now()->subDays(4)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        ExcuseSubmission::create([
            'user_id' => $teacher->id,
            'attendance_id' => $teacherOwnAtt->id,
            'reason' => 'Faculty Symposium Travel',
            'description' => 'Presented paper',
            'status' => 'pending',
        ]);

        // Another teacher's student excuse and correction
        $otherAtt = Attendance::create([
            'user_id' => $otherStudent->id,
            'subject_code' => $otherSubject->code,
            'date' => now()->subDays(2)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        ExcuseSubmission::create([
            'user_id' => $otherStudent->id,
            'attendance_id' => $otherAtt->id,
            'reason' => 'Secret OtherClass Excuse',
            'description' => 'Should stay hidden',
            'status' => 'pending',
        ]);
        AttendanceCorrection::create([
            'attendance_id' => $otherAtt->id,
            'student_id' => $otherStudent->id,
            'reason' => 'Secret OtherClass Correction',
            'status' => 'pending',
        ]);

        $html = $this->actingAs($teacher)->get(route('teacher.excuse.reviews'))->assertOk()->getContent();

        $excusesPanel = $this->extractHtmlBlock($html, '/<section\s+id="excuses-tab".*?<section\s+id="corrections-tab"/s');
        $correctionsPanel = $this->extractHtmlBlock($html, '/<section\s+id="corrections-tab".*?<section\s+id="my-excuses-tab"/s');
        $myExcusesPanel = $this->extractHtmlBlock($html, '/<section\s+id="my-excuses-tab".*?<script @?cspNonce|<section\s+id="my-excuses-tab".*?<\/div>\s*<script/s');

        $this->assertStringContainsString('MyStudent Fever Excuse', $excusesPanel);
        $this->assertStringNotContainsString('Faculty Symposium Travel', $excusesPanel);
        $this->assertStringNotContainsString('Secret OtherClass Excuse', $excusesPanel);

        $this->assertStringContainsString('MyClass QR scanner timeout', $correctionsPanel);
        $this->assertStringNotContainsString('Secret OtherClass Correction', $correctionsPanel);

        $this->assertStringContainsString('Faculty Symposium Travel', $myExcusesPanel);
        $this->assertStringNotContainsString('MyStudent Fever Excuse', $myExcusesPanel);
    }

    public function test_filtering_correction_approval_and_teacher_excuse_submission_work_end_to_end(): void
    {
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student', ['name' => 'Nora Student']);
        $subject = $this->createSubject($teacher, 'CS530', 'Compiler Design');

        $attExcusePending = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => now()->subDays(1)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        ExcuseSubmission::create([
            'user_id' => $student->id,
            'attendance_id' => $attExcusePending->id,
            'reason' => 'Pending Student Flu',
            'description' => 'Home rest',
            'status' => 'pending',
        ]);

        $attCorrPending = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => now()->subDays(2)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);
        $pendingCorrection = AttendanceCorrection::create([
            'attendance_id' => $attCorrPending->id,
            'student_id' => $student->id,
            'reason' => 'Pending Correction Checkin',
            'status' => 'pending',
        ]);

        $attCorrApproved = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => now()->subDays(3)->toDateString(),
            'status' => 'Present',
            'excused' => false,
        ]);
        AttendanceCorrection::create([
            'attendance_id' => $attCorrApproved->id,
            'student_id' => $student->id,
            'reason' => 'Approved Correction Checkin',
            'status' => 'approved',
        ]);

        // Filtering Corrections by ?status=approved must filter corrections WITHOUT dropping pending student excuses
        $filteredHtml = $this->actingAs($teacher)
            ->get(route('teacher.corrections', ['status' => 'approved']))
            ->assertOk()
            ->getContent();

        $excusesPanel = $this->extractHtmlBlock($filteredHtml, '/<section\s+id="excuses-tab".*?<section\s+id="corrections-tab"/s');
        $correctionsPanel = $this->extractHtmlBlock($filteredHtml, '/<section\s+id="corrections-tab".*?<section\s+id="my-excuses-tab"/s');

        $this->assertStringContainsString('Pending Student Flu', $excusesPanel);
        $this->assertStringContainsString('Approved Correction Checkin', $correctionsPanel);
        $this->assertStringNotContainsString('Pending Correction Checkin', $correctionsPanel);

        // Approve pending correction via POST route
        $this->actingAs($teacher)
            ->from(route('teacher.corrections'))
            ->post(route('teacher.corrections.update', $pendingCorrection), ['action' => 'approve'])
            ->assertRedirect(route('teacher.corrections'));

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $pendingCorrection->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('attendances', [
            'id' => $attCorrPending->id,
            'status' => 'Present',
        ]);

        // Submit teacher excuse via POST route -> redirects to teacher.excuses -> renders My Excuses tab
        $response = $this->actingAs($teacher)
            ->post(route('teacher.excuses.store'), [
                'subject_code' => $subject->code,
                'date' => now()->toDateString(),
                'reason' => 'Faculty Workshop Leave',
                'description' => 'Representing department at workshop',
            ]);

        $response->assertRedirect(route('teacher.excuses'));
        $redirectedHtml = $this->actingAs($teacher)->get(route('teacher.excuses'))->assertOk()->getContent();
        $this->assertStringContainsString('Faculty Workshop Leave', $redirectedHtml);
        $this->assertMatchesRegularExpression('/id="tab-btn-my-excuses".*?aria-selected="true"/s', $redirectedHtml);

        // Regression checks for filter grid layout and button styling
        $this->assertStringContainsString('class="tch-filter-grid"', $redirectedHtml);
        $this->assertStringContainsString('class="tch-filter-actions"', $redirectedHtml);
        $this->assertStringNotContainsString('<div class="col-md-2" style="display: flex; align-items: end;', $redirectedHtml);

        $masterCss = file_get_contents(public_path('css/teacher-portal-master.css'));
        $this->assertIsString($masterCss);
        $this->assertStringContainsString('body.teacher-portal .tch-btn', $masterCss);
        $this->assertStringContainsString('display: inline-flex !important;', $masterCss);
        $this->assertStringContainsString('text-decoration: none !important;', $masterCss);
    }

    public function test_submit_excuse_form_renders_unified_frontend_and_handles_empty_or_assigned_subjects(): void
    {
        $teacherWithoutSubjects = $this->createUser('teacher');
        $emptyHtml = $this->actingAs($teacherWithoutSubjects)->get(route('teacher.excuses.create'))->assertOk()->getContent();
        $this->assertStringContainsString('No Assigned Classes Found', $emptyHtml);
        $this->assertStringContainsString(route('teacher.subjects.create'), $emptyHtml);

        $this->flushSession();

        $teacher = $this->createUser('teacher');
        $subject = $this->createSubject($teacher, 'CS540', 'Distributed Databases');

        $html = $this->actingAs($teacher)->get(route('teacher.excuses.create'))->assertOk()->getContent();
        $workspaceHtml = $this->extractHtmlBlock($html, '/<div class="texc-workspace">.*?<\/main>/s');
        $this->assertNotEmpty($workspaceHtml);

        $this->assertStringContainsString('class="texc-workspace"', $workspaceHtml);
        $this->assertStringContainsString('class="texc-grid"', $workspaceHtml);
        $this->assertStringContainsString('data-reason-chip=', $workspaceHtml);
        $this->assertStringContainsString('id="dropZone" class="texc-dropzone"', $workspaceHtml);
        $this->assertStringContainsString('maxlength="1000"', $workspaceHtml);
        $this->assertStringContainsString(route('teacher.excuses.store'), $workspaceHtml);
        $this->assertStringContainsString('ui-back-link', $workspaceHtml);
        $this->assertStringContainsString($subject->code, $workspaceHtml);
        $this->assertStringContainsString($subject->name, $workspaceHtml);

        $this->assertStringNotContainsString('onfocus=', $workspaceHtml);
        $this->assertStringNotContainsString('onmouseover=', $workspaceHtml);
        $this->assertStringNotContainsString('onchange="handleFileSelect', $workspaceHtml);

        $masterCss = file_get_contents(public_path('css/teacher-portal-master.css'));
        $this->assertIsString($masterCss);
        $this->assertStringContainsString('.texc-workspace', $masterCss);
        $this->assertStringContainsString('.texc-grid', $masterCss);
        $this->assertStringContainsString('.texc-dropzone', $masterCss);
        $this->assertStringContainsString('.texc-reason-chip', $masterCss);
    }
}
