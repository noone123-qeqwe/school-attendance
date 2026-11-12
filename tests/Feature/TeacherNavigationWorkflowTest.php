<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherNavigationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_picker_search_keeps_the_task_and_only_returns_owned_classes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'UX101', 'name' => 'Research and Design with a Long Subject Name']);
        Subject::factory()->create(['code' => 'UX102', 'name' => 'Research and Design Private Class']);
        $this->actingAs($teacher);

        $response = $this->get(route('teacher.classroom.index', ['task' => 'attendance', 'search' => 'Research and Design']))->assertOk();
        $response->assertViewHas('subjects', fn ($subjects) => $subjects->modelKeys() === [$subject->id]);
        $response->assertSee(route('teacher.qr', $subject->code), false);
        $response->assertDontSee('Research and Design Private Class');

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame('attendance', $xpath->query('//form[@role="search"]//input[@name="task"]')->item(0)?->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//aside//a[@aria-current="page" and contains(@href, "task=attendance")]')->length);
        $this->assertSame(0, $xpath->query('//aside//a[@aria-current="page" and @href="'.route('teacher.classroom.index').'"]')->length);

        $this->capture('attendance-picker', $response->getContent());
        if (getenv('TEACHER_UI_AUDIT_OUTPUT') === '1') {
            foreach (['dashboard', 'classroom.index', 'attendance', 'reports', 'students', 'excuse.reviews', 'corrections', 'profile'] as $page) {
                $pageResponse = $this->get(route('teacher.'.$page))->assertOk();
                $this->capture(str_replace('.', '-', $page), $pageResponse->getContent());
            }
        }
    }

    public function test_todays_class_record_link_opens_only_that_subject_and_date(): void
    {
        $teacher = User::factory()->teacher()->create();
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'TODAY101']);
        $otherSubject = Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'OTHER101']);
        $subject->schedules()->create(['day' => now()->format('l'), 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'room' => 'Room 101']);
        $student = User::factory()->create(['role' => 'student']);
        foreach ([[$subject, today()], [$subject, today()->subDay()], [$otherSubject, today()]] as [$class, $date]) {
            Attendance::withoutEvents(fn () => Attendance::create([
                'user_id' => $student->id, 'subject_id' => $class->id, 'subject_code' => $class->code,
                'date' => $date->toDateString(), 'status' => 'Present',
            ]));
        }
        $this->actingAs($teacher);

        $dashboard = $this->get(route('teacher.dashboard'))->assertOk();
        $url = route('teacher.attendance', ['subject' => $subject->code, 'date' => today()->toDateString()]);
        $dashboard->assertSee($url);
        $this->get($url)->assertOk()->assertViewHas('attendanceRecords', fn ($records) => $records->count() === 1
            && $records->first()->subject_code === $subject->code
            && \Carbon\Carbon::parse($records->first()->date)->isToday());
        $this->capture('dashboard-with-class', $dashboard->getContent());
    }

    private function capture(string $name, string $html): void
    {
        if (getenv('TEACHER_UI_AUDIT_OUTPUT') !== '1') return;
        $directory = storage_path('framework/testing/teacher-ui-audit');
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        file_put_contents($directory.'/'.$name.'.html', $html);
    }
}
