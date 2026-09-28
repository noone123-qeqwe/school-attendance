<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_renders()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $response = $this->actingAs($teacher)
                         ->withSession(['user_role' => 'teacher'])
                         ->get('/teacher/dashboard');
        
        $response->assertStatus(200);
        file_put_contents(base_path('test_response.html'), $response->getContent());
    }

    public function test_student_with_no_attendance_sees_an_unrated_dashboard(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 07:00:00', 'Asia/Manila'));

        try {
            $student = User::factory()->create(['role' => 'student']);

            $response = $this->actingAs($student)->get('/home');

            $response->assertOk();
            $response->assertViewHas('attendanceRate', null);
            $response->assertSee('No records yet');
            $response->assertSee('Attendance rate available after your first recorded class');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_student_dashboard_shows_explicitly_enrolled_class_outside_profile(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00', 'Asia/Manila'));

        try {
            $student = User::factory()->create([
                'role' => 'student',
                'year_level' => 2,
                'semester' => 1,
                'course' => 'BSCS',
                'section' => 'A',
                'created_at' => Carbon::parse('2026-10-04 07:00:00', 'Asia/Manila'),
            ]);
            $subject = Subject::create([
                'code' => 'CROSS101',
                'name' => 'Interdisciplinary Seminar',
                'year_level' => 3,
                'semester' => 1,
                'course' => 'BSED',
                'section' => 'B',
            ]);
            Schedule::create([
                'subject_id' => $subject->id,
                'day' => 'Monday',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
            ]);
            $student->enrolledSubjects()->attach($subject->id);

            $response = $this->actingAs($student)->get('/home');

            $response->assertOk();
            $response->assertViewHas('currentClass', fn ($class) => $class?->id === $subject->id);
            $response->assertViewHas('todaySchedule', fn ($schedule) => $schedule->contains(
                fn ($item) => $item->subject->id === $subject->id
            ));
            $response->assertSee('Interdisciplinary Seminar');
        } finally {
            Carbon::setTestNow();
        }
    }
}
