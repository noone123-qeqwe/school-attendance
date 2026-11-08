<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentNotificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_parent_sees_parent_owned_absence_alert_and_not_unrelated_alerts(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $otherParent = User::factory()->create(['role' => 'parent']);
        $student = User::factory()->create(['role' => 'student']);
        $otherChild = User::factory()->create(['role' => 'student']);
        $parent->children()->attach($student->id);
        $parent->children()->attach($otherChild->id);
        $otherParent->children()->attach($student->id);

        $ownAlert = $this->notification($parent, 'Own linked-child absence', 'absence_alert');
        $ownAlert->update(['related_user_id' => $student->id]);
        $this->notification($parent, 'Other linked-child absence', 'absence_alert')
            ->update(['related_user_id' => $otherChild->id]);
        $childAlert = $this->notification($student, 'Linked child information', 'absence');
        $this->notification($otherParent, 'Unrelated private alert', 'absence_alert');

        $this->actingAs($parent)->get(route('parent.notifications'))
            ->assertOk()
            ->assertSee('Own linked-child absence')
            ->assertSee('Linked child information')
            ->assertDontSee('Unrelated private alert');

        $this->actingAs($parent)->get(route('parent.notifications', ['type' => 'absence']))
            ->assertOk()
            ->assertSee('Own linked-child absence');

        $filtered = $this->actingAs($parent)->get(route('parent.notifications', ['child_id' => $student->id]));
        $filtered->assertOk();
        $filteredMessages = $filtered->viewData('notifications')->getCollection()->pluck('message')->all();
        $this->assertContains('Own linked-child absence', $filteredMessages);
        $this->assertContains('Linked child information', $filteredMessages);
        $this->assertNotContains('Other linked-child absence', $filteredMessages);

        $unlinked = $this->actingAs($parent)->get(route('parent.notifications', ['child_id' => $otherParent->id]));
        $unlinked->assertOk();
        $this->assertCount(0, $unlinked->viewData('notifications'));

        $this->actingAs($parent)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 3)
            ->assertJsonFragment(['id' => $ownAlert->id, 'message' => 'Own linked-child absence']);

        $this->actingAs($parent)->post(route('parent.notifications.read'))->assertOk();
        $this->assertDatabaseHas('notifications', ['id' => $ownAlert->id, 'is_read' => true]);
        $this->assertDatabaseHas('parent_notification_reads', ['parent_id' => $parent->id, 'notification_id' => $childAlert->id]);
        $this->assertDatabaseHas('notifications', ['id' => $childAlert->id, 'is_read' => false]);

        $this->actingAs($otherParent)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonFragment(['id' => $childAlert->id, 'is_read' => false]);
    }

    public function test_notification_poll_is_available_to_verified_roles_but_not_unverified_admin_or_guest(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create(['admin_sub_role' => 'super_admin']);
        $this->notification($teacher, 'Teacher private alert', 'system_update');
        $this->notification($admin, 'Admin private alert', 'system_update');

        $this->getJson(route('notifications.poll'))->assertUnauthorized();

        $this->actingAs($teacher)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonFragment(['message' => 'Teacher private alert']);

        $this->actingAs($admin)->withSession(['admin_2fa_verified' => false])
            ->getJson(route('notifications.poll'))->assertForbidden();
        $this->actingAs($admin)->withSession(['admin_2fa_verified' => true])
            ->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonFragment(['message' => 'Admin private alert']);
    }

    private function notification(User $recipient, string $message, string $type): Notification
    {
        return Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
