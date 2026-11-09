<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Notification;
use App\Models\User;
use App\Services\LegacyParentAlertBackfillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyParentAlertBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_unambiguous_alerts_with_a_matching_absence_are_backfilled(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Reliable Student']);
        $sameNameStudent = User::factory()->create(['role' => 'student', 'name' => 'Same Name']);
        $otherSameNameStudent = User::factory()->create(['role' => 'student', 'name' => 'Same Name']);
        $parent->children()->attach([$student->id, $sameNameStudent->id, $otherSameNameStudent->id]);

        Attendance::withoutEvents(fn () => Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'CS101',
            'subject_name' => 'CS101',
            'status' => 'Absent',
            'date' => '2026-11-09',
        ]));

        $reliable = $this->alert($parent, 'Reliable Student was marked Absent in CS101 on Nov 09, 2026.', 'CS101');
        $ambiguous = $this->alert($parent, 'Same Name was marked Absent in CS101 on Nov 09, 2026.', 'CS101');
        $noAttendance = $this->alert($parent, 'Reliable Student was marked Absent in CS102 on Nov 09, 2026.', 'CS102');
        $malformed = $this->alert($parent, 'Reliable Student absence notice', 'CS101');

        $this->assertSame(1, app(LegacyParentAlertBackfillService::class)->run());
        $this->assertSame($student->id, $reliable->fresh()->related_user_id);
        $this->assertNull($ambiguous->fresh()->related_user_id);
        $this->assertNull($noAttendance->fresh()->related_user_id);
        $this->assertNull($malformed->fresh()->related_user_id);
    }

    private function alert(User $parent, string $message, string $subjectCode): Notification
    {
        return Notification::create([
            'user_id' => $parent->id,
            'type' => 'absence_alert',
            'subject_code' => $subjectCode,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
