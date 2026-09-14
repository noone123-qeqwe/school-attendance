<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000001',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000002',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);
    }

    public function test_student_can_view_redesigned_notifications_page(): void
    {
        Notification::create([
            'user_id' => $this->student->id,
            'type' => 'absence',
            'subject_code' => 'CS101',
            'message' => 'You were marked absent in CS101.',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->student)->get(route('notifications'));

        $response->assertStatus(200);
        $response->assertSee('Notifications');
        $response->assertSee('Active Notices');
        $response->assertSee('You were marked absent in CS101.');
        $response->assertSee('Mark All as Read');
    }

    public function test_student_can_filter_notifications_by_status(): void
    {
        $active = Notification::create([
            'user_id' => $this->student->id,
            'type' => 'absence',
            'message' => 'Active Notice Message',
            'is_read' => false,
        ]);

        $archived = Notification::create([
            'user_id' => $this->student->id,
            'type' => 'system_update',
            'message' => 'Archived Notice Message',
            'is_read' => true,
            'archived_at' => now(),
        ]);

        // Active filter
        $activeRes = $this->actingAs($this->student)->get(route('notifications', ['status' => 'active']));
        $activeRes->assertStatus(200);
        $activeRes->assertSee("notifCard_{$active->id}");
        $activeRes->assertDontSee("notifCard_{$archived->id}");

        // Archived filter
        $archivedRes = $this->actingAs($this->student)->get(route('notifications', ['status' => 'archived']));
        $archivedRes->assertStatus(200);
        $archivedRes->assertSee("notifCard_{$archived->id}");
        $archivedRes->assertDontSee("notifCard_{$active->id}");
    }

    public function test_student_can_mark_single_notification_read(): void
    {
        $notif = Notification::create([
            'user_id' => $this->student->id,
            'type' => 'absence',
            'message' => 'Unread absence message',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('notifications.markRead', $notif));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertTrue($notif->fresh()->is_read);
    }

    public function test_student_cannot_mark_other_students_notification_read(): void
    {
        $otherNotif = Notification::create([
            'user_id' => $this->otherStudent->id,
            'type' => 'absence',
            'message' => 'Other student notice',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('notifications.markRead', $otherNotif));

        $response->assertStatus(403);
        $this->assertFalse($otherNotif->fresh()->is_read);
    }

    public function test_student_can_mark_all_notifications_read(): void
    {
        Notification::create([
            'user_id' => $this->student->id,
            'type' => 'absence',
            'message' => 'Notice 1',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $this->student->id,
            'type' => 'warning_2',
            'message' => 'Notice 2',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('notifications.read'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $unreadCount = Notification::where('user_id', $this->student->id)->where('is_read', false)->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_student_can_archive_and_unarchive_notification(): void
    {
        $notif = Notification::create([
            'user_id' => $this->student->id,
            'type' => 'warning_2',
            'message' => 'Notice to archive',
            'is_read' => false,
        ]);

        // Archive
        $archiveRes = $this->actingAs($this->student)
            ->postJson(route('notifications.archive', $notif));
        $archiveRes->assertStatus(200);
        $this->assertTrue($notif->fresh()->isArchived());

        // Unarchive
        $unarchiveRes = $this->actingAs($this->student)
            ->postJson(route('notifications.unarchive', $notif));
        $unarchiveRes->assertStatus(200);
        $this->assertFalse($notif->fresh()->isArchived());
    }

    public function test_student_can_delete_notification(): void
    {
        $notif = Notification::create([
            'user_id' => $this->student->id,
            'type' => 'warning_2',
            'message' => 'Notice to delete',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->deleteJson(route('notifications.delete', $notif));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
    }
}
