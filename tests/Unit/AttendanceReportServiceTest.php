<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttendanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_filters_by_subject_and_aggregates_status_counts(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'REPORT-101']);
        Attendance::create([
            'user_id' => $student->id, 'subject_id' => $subject->id, 'subject_code' => $subject->code,
            'date' => today()->toDateString(), 'status' => 'Present', 'time_in' => '09:00:00',
        ]);
        Attendance::create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'subject_id' => $subject->id, 'subject_code' => 'OTHER-101',
            'date' => today()->toDateString(), 'status' => 'Absent',
        ]);

        $report = app(AttendanceReportService::class)->generate([
            'type' => 'daily', 'date' => today()->toDateString(), 'subject' => $subject->code,
        ], [$subject->code]);

        $this->assertSame(1, $report['stats']['total']);
        $this->assertSame(1, $report['stats']['present']);
        $this->assertSame(0, $report['stats']['absent']);
        $this->assertCount(1, $report['records']);
        $this->assertSame($subject->code, $report['records']->first()->subject_code);
    }
}
