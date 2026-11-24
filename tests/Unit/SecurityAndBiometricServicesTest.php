<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Otp;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\AbsenceAlert;
use App\Services\PeerFaceVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAndBiometricServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_failed_verify_lock_threshold_and_reset_on_generate(): void
    {
        $user = User::create([
            'name' => 'Admin OTP Test',
            'student_number' => 'ADM-001',
            'course' => 'ADMIN',
            'year_level' => '1',
            'email' => 'admin.otp@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'is_active' => true,
        ]);

        Cache::flush();

        $otp = Otp::generate($user->id, 'admin_login');
        $this->assertFalse(Otp::hasExceededVerifyAttempts($user->id, 'admin_login'));

        for ($i = 0; $i < Otp::MAX_VERIFY_ATTEMPTS; $i++) {
            Otp::recordFailedVerify($user->id, 'admin_login');
        }

        $this->assertTrue(Otp::hasExceededVerifyAttempts($user->id, 'admin_login'));

        // Generating a fresh OTP clears previous failure attempts and invalidates prior active OTPs
        $fresh = Otp::generate($user->id, 'admin_login');
        $this->assertFalse(Otp::hasExceededVerifyAttempts($user->id, 'admin_login'));
        $this->assertTrue((bool) $otp->fresh()->used);
        $this->assertFalse((bool) $fresh->fresh()->used);
    }

    public function test_announcement_visibility_scope_matches_all_student_parent_teacher_and_subject(): void
    {
        $admin = User::create([
            'name' => 'Ann Author',
            'student_number' => 'ADM-ANN',
            'course' => 'ADMIN',
            'year_level' => '1',
            'email' => 'author@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'is_active' => true,
        ]);

        $teacher = User::create([
            'name' => 'Prof Santos',
            'student_number' => 'TCH-01',
            'course' => 'FACULTY',
            'year_level' => '1',
            'email' => 'teacher.ann@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $student = User::create([
            'name' => 'Juan Student',
            'student_number' => '2026-001',
            'course' => 'BSIT',
            'year_level' => '2',
            'semester' => '1',
            'section' => 'A',
            'email' => 'juan.ann@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $parent = User::create([
            'name' => 'Maria Parent',
            'student_number' => 'PAR-01',
            'course' => 'PARENT',
            'year_level' => '1',
            'email' => 'maria.parent@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'parent',
            'is_active' => true,
        ]);

        $subject = Subject::create([
            'code' => 'IT201',
            'name' => 'Systems Integration',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSIT',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);
        $student->enrolledSubjects()->attach($subject->id);
        $parent->children()->attach($student->id);

        $allAnn = Announcement::create([
            'title' => 'Campus Notice',
            'content' => 'Welcome to Osmena Colleges.',
            'target_audience' => 'All',
            'author_id' => $admin->id,
            'scheduled_for' => now()->subMinute(),
        ]);
        $studentOnlyAnn = Announcement::create([
            'title' => 'Student Reminder',
            'content' => 'Bring student ID.',
            'target_audience' => 'Student',
            'author_id' => $admin->id,
            'scheduled_for' => now()->subMinute(),
        ]);
        $parentOnlyAnn = Announcement::create([
            'title' => 'Parent PTA Notice',
            'content' => 'PTA assembly Friday.',
            'target_audience' => 'Parent',
            'author_id' => $admin->id,
            'scheduled_for' => now()->subMinute(),
        ]);
        $teacherOnlyAnn = Announcement::create([
            'title' => 'Faculty Meeting',
            'content' => 'Grades submission.',
            'target_audience' => 'Teacher',
            'author_id' => $admin->id,
            'scheduled_for' => now()->subMinute(),
        ]);
        $subjectAnn = Announcement::create([
            'title' => 'IT201 Quiz',
            'content' => 'Quiz next class.',
            'target_audience' => 'Subject',
            'target_id' => $subject->id,
            'author_id' => $teacher->id,
            'scheduled_for' => now()->subMinute(),
        ]);

        $studentVisibleIds = Announcement::published()->visibleTo($student)->pluck('id')->all();
        $this->assertContains($allAnn->id, $studentVisibleIds);
        $this->assertContains($studentOnlyAnn->id, $studentVisibleIds);
        $this->assertContains($subjectAnn->id, $studentVisibleIds);
        $this->assertNotContains($parentOnlyAnn->id, $studentVisibleIds);
        $this->assertNotContains($teacherOnlyAnn->id, $studentVisibleIds);

        $parentVisibleIds = Announcement::published()->visibleTo($parent)->pluck('id')->all();
        $this->assertContains($allAnn->id, $parentVisibleIds);
        $this->assertContains($parentOnlyAnn->id, $parentVisibleIds);
        $this->assertContains($subjectAnn->id, $parentVisibleIds);
        $this->assertNotContains($studentOnlyAnn->id, $parentVisibleIds);
        $this->assertNotContains($teacherOnlyAnn->id, $parentVisibleIds);

        $teacherVisibleIds = Announcement::published()->visibleTo($teacher)->pluck('id')->all();
        $this->assertContains($allAnn->id, $teacherVisibleIds);
        $this->assertContains($teacherOnlyAnn->id, $teacherVisibleIds);
        $this->assertContains($subjectAnn->id, $teacherVisibleIds);
        $this->assertNotContains($parentOnlyAnn->id, $teacherVisibleIds);
    }

    public function test_absence_alert_uses_recipient_appropriate_wording_for_student_vs_parent(): void
    {
        $student = User::create([
            'name' => 'Pedro Cruz',
            'student_number' => '2026-002',
            'course' => 'BSCS',
            'year_level' => '1',
            'email' => 'pedro@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $parent = User::create([
            'name' => 'Elena Cruz',
            'student_number' => 'PAR-02',
            'course' => 'PARENT',
            'year_level' => '1',
            'email' => 'elena@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'parent',
            'is_active' => true,
        ]);

        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'CS101',
            'subject_name' => 'Intro to CS',
            'date' => '2026-11-24',
            'status' => 'Absent',
            'excused' => false,
        ]);

        $alert = new AbsenceAlert($attendance);

        $studentMail = $alert->toMail($student);
        $this->assertStringContainsString('You were marked', implode(' ', $studentMail->introLines));
        $this->assertStringNotContainsString('Your child', implode(' ', $studentMail->introLines));
        $this->assertStringContainsString('/home', (string) $studentMail->actionUrl);
        $this->assertStringContainsString('You were marked Absent', $alert->toSemaphore($student));
        $this->assertStringNotContainsString('Your child', $alert->toArray($student)['message']);

        $parentMail = $alert->toMail($parent);
        $this->assertStringContainsString('Your child, **Pedro Cruz**', implode(' ', $parentMail->introLines));
        $this->assertStringContainsString('/parent/dashboard', (string) $parentMail->actionUrl);
        $this->assertStringContainsString('Pedro Cruz was marked Absent', $alert->toSemaphore($parent));
        $this->assertStringContainsString('Your child, Pedro Cruz', $alert->toArray($parent)['message']);
    }

    public function test_peer_face_verifier_supports_local_mode_when_models_exist_and_external_when_https_configured(): void
    {
        $tmpDir = storage_path('app/private/test-models-' . uniqid('', true));
        @mkdir($tmpDir, 0755, true);
        $detector = $tmpDir . '/yunet.onnx';
        $recognizer = $tmpDir . '/sface.onnx';
        $pad = $tmpDir . '/pad.onnx';
        file_put_contents($detector, 'dummy');
        file_put_contents($recognizer, 'dummy');
        file_put_contents($pad, 'dummy');

        try {
            config([
                'peer_snap.enabled' => true,
                'peer_snap.verifier_url' => '',
                'peer_snap.verifier_token' => '',
                'peer_snap.model_version' => '',
                'peer_snap.local_model_version' => 'opencv_yunet_sface_v1',
                'peer_snap.local_match_threshold' => 0.363,
                'peer_snap.pad_threshold' => 0.90,
                'face_match.detector_model' => $detector,
                'face_match.recognizer_model' => $recognizer,
                'face_match.attendance_pad_model' => $pad,
            ]);

            $verifier = app(PeerFaceVerifier::class);
            $this->assertFalse($verifier->usesExternalVerifier());
            $this->assertTrue($verifier->usesLocalVerifier());
            $this->assertTrue($verifier->isAvailable());
            $this->assertSame('opencv_yunet_sface_v1', $verifier->activeModelVersion());

            config([
                'peer_snap.verifier_url' => 'https://verifier.example.invalid/verify',
                'peer_snap.verifier_token' => 'secret-token',
                'peer_snap.model_version' => 'remote-v2',
                'peer_snap.match_threshold' => 0.85,
            ]);

            $this->assertTrue($verifier->usesExternalVerifier());
            $this->assertFalse($verifier->usesLocalVerifier());
            $this->assertSame('remote-v2', $verifier->activeModelVersion());
        } finally {
            @unlink($detector);
            @unlink($recognizer);
            @unlink($pad);
            @rmdir($tmpDir);
        }
    }
}
