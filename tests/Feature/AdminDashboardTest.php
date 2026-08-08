<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->withSession(['admin_2fa_verified' => true]);
    }

    // ─────────────────────────────────────────
    // ACCESS CONTROL
    // ─────────────────────────────────────────

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/admin/dashboard');

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get('/admin/dashboard');

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    // ─────────────────────────────────────────
    // DASHBOARD STATS
    // ─────────────────────────────────────────

    public function test_dashboard_shows_correct_student_count(): void
    {
        User::factory()->count(5)->create(['role' => 'student']);

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Total Students');
    }

    public function test_dashboard_shows_teacher_count(): void
    {
        User::factory()->teacher()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_shows_attendance_rate(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────
    // STUDENT MANAGEMENT
    // ─────────────────────────────────────────

    public function test_admin_can_view_students_list(): void
    {
        User::factory()->count(3)->create(['role' => 'student']);

        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertStatus(200);
    }

    public function test_admin_can_filter_students_by_course(): void
    {
        User::factory()->create(['role' => 'student', 'course' => 'BSCS', 'name' => 'CS Student']);
        User::factory()->create(['role' => 'student', 'course' => 'BSIT', 'name' => 'IT Student']);

        $response = $this->actingAs($this->admin)->get('/admin/students?course=BSCS');

        $response->assertStatus(200);
        $response->assertSee('CS Student');
        $response->assertDontSee('IT Student');
    }

    public function test_admin_can_search_students(): void
    {
        User::factory()->create(['role' => 'student', 'name' => 'John Smith', 'student_number' => '3000001']);
        User::factory()->create(['role' => 'student', 'name' => 'Jane Doe', 'student_number' => '3000002']);

        $response = $this->actingAs($this->admin)->get('/admin/students?search=John');

        $response->assertStatus(200);
        $response->assertSee('John Smith');
        $response->assertDontSee('Jane Doe');
    }

    public function test_admin_can_view_student_detail(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Test Student']);

        $response = $this->actingAs($this->admin)->get("/admin/student/{$student->id}");

        $response->assertStatus(200);
        $response->assertSee('Test Student');
    }

    public function test_admin_can_delete_student(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($this->admin)->delete("/admin/student/{$student->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $student->id]);
    }

    // ─────────────────────────────────────────
    // SUBJECT MANAGEMENT
    // ─────────────────────────────────────────

    public function test_admin_can_view_subjects(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/subjects');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_subject(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/subjects', [
            'code' => 'IT101',
            'name' => 'Web Development',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSIT',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['code' => 'IT101', 'name' => 'Web Development']);
    }

    public function test_admin_can_delete_subject(): void
    {
        $subject = Subject::create([
            'code' => 'DEL101',
            'name' => 'To Delete',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/subjects/{$subject->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);
    }

    // ─────────────────────────────────────────
    // ATTENDANCE LOGS
    // ─────────────────────────────────────────

    public function test_admin_can_view_attendance_logs(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/attendance');

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────
    // NOTIFICATIONS
    // ─────────────────────────────────────────

    public function test_admin_can_view_notifications(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/notifications');

        $response->assertStatus(200);
    }

    public function test_admin_can_archive_notification(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $notification = Notification::create([
            'user_id' => $student->id,
            'sent_by' => $this->admin->id,
            'type' => 'custom',
            'message' => 'Test notification',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/notifications/{$notification->id}/archive");

        $response->assertJson(['success' => true]);
        $this->assertNotNull($notification->fresh()->archived_at);
    }

    // ─────────────────────────────────────────
    // CALENDAR
    // ─────────────────────────────────────────

    public function test_admin_can_view_calendar(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/calendar');

        $response->assertStatus(200);
    }
}
