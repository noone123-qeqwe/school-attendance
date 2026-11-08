<?php

namespace Tests\Feature;

use App\Exports\AttendanceExport;
use App\Exports\StudentsExport;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TeacherDataScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_without_subjects_cannot_read_other_classes_in_reports_or_exports(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = $this->student('Private Student', 'A');
        $subject = $this->subject('PRIVATE-947', $otherTeacher);

        Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => today()->toDateString(),
            'status' => 'Present',
            'time_in' => '09:00:00',
        ]);

        foreach (['daily', 'class_summary', 'subject_summary', 'percentage'] as $type) {
            $this->actingAs($teacher)->get(route('teacher.reports', ['type' => $type]))
                ->assertOk()
                ->assertDontSee('Private Student')
                ->assertDontSee($subject->code);
        }

        $reportCsv = $this->actingAs($teacher)->get(route('teacher.reports.csv', ['type' => 'daily']));
        $reportCsv->assertOk();
        $this->assertStringNotContainsString($subject->code, $reportCsv->streamedContent());

        Excel::fake();
        $this->actingAs($teacher)->get(route('teacher.attendance.csv'))->assertOk();
        Excel::assertDownloaded('attendance.csv', fn (AttendanceExport $export) => $export->collection()->isEmpty());

        $this->actingAs($teacher)->get(route('teacher.students'))->assertOk()->assertDontSee('Private Student');
        $this->actingAs($teacher)->get(route('teacher.students.preview'))->assertOk()->assertDontSee('Private Student');
    }

    public function test_teacher_roster_and_percentage_report_include_only_own_section_and_explicit_enrollments(): void
    {
        $teacher = User::factory()->teacher()->create();
        $subject = $this->subject('OWN-917', $teacher);
        $sectionStudent = $this->student('Assigned Section Student', 'A');
        $otherSectionStudent = $this->student('Unrelated Section Student', 'B');
        $explicitStudent = $this->student('Explicitly Enrolled Student', 'B');
        $subject->enrolledStudents()->attach($explicitStudent->id);

        $studentsPage = $this->actingAs($teacher)->get(route('teacher.students'));
        $studentsPage->assertOk()
            ->assertSee($sectionStudent->name)
            ->assertSee($explicitStudent->name)
            ->assertDontSee($otherSectionStudent->name);

        $preview = $this->actingAs($teacher)->get(route('teacher.students.preview'));
        $preview->assertOk()
            ->assertSee($sectionStudent->name)
            ->assertSee($explicitStudent->name)
            ->assertDontSee($otherSectionStudent->name);

        $ranking = $this->actingAs($teacher)->get(route('teacher.reports', ['type' => 'percentage']));
        $ranking->assertOk()
            ->assertSee($sectionStudent->name)
            ->assertSee($explicitStudent->name)
            ->assertDontSee($otherSectionStudent->name);

        Excel::fake();
        $this->actingAs($teacher)->get(route('teacher.students.csv'))->assertOk();
        Excel::assertDownloaded('students.csv', function (StudentsExport $export) use ($sectionStudent, $explicitStudent, $otherSectionStudent) {
            $names = $export->collection()->pluck('name')->all();

            return in_array($sectionStudent->name, $names, true)
                && in_array($explicitStudent->name, $names, true)
                && !in_array($otherSectionStudent->name, $names, true);
        });
    }

    public function test_teacher_cannot_request_another_subject_in_direct_attendance_export(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $subject = $this->subject('OTHER-619', $otherTeacher);

        $this->actingAs($teacher)->get(route('teacher.attendance.csv', ['subject_code' => $subject->code]))
            ->assertForbidden();
    }

    public function test_subject_code_with_attendance_records_cannot_be_renamed_by_its_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = $this->student('Assigned Student', 'A');
        $subject = $this->subject('STABLE-319', $teacher);
        Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => today()->toDateString(),
            'status' => 'Present',
            'time_in' => '09:00:00',
        ]);

        $this->actingAs($teacher)->put(route('teacher.subjects.update', $subject), [
            'code' => 'RENAMED-319',
            'name' => $subject->name,
            'year_level' => $subject->year_level,
            'semester' => $subject->semester,
            'units' => $subject->units,
            'section' => $subject->section,
        ])->assertSessionHasErrors('code');

        $this->assertSame('STABLE-319', $subject->fresh()->code);
    }

    private function student(string $name, string $section): User
    {
        return User::factory()->create([
            'role' => 'student',
            'name' => $name,
            'course' => 'BSCS',
            'year_level' => 1,
            'semester' => 1,
            'section' => $section,
        ]);
    }

    private function subject(string $code, User $teacher): Subject
    {
        return Subject::factory()->create([
            'code' => $code,
            'instructor_id' => $teacher->id,
            'course' => 'BSCS',
            'year_level' => 1,
            'semester' => 1,
            'section' => 'A',
        ]);
    }
}
