<?php

namespace App\Services;

use App\Models\User;
use App\Models\Otp;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ParentService
{
    /**
     * Start the linking process by finding the student and sending an OTP to them.
     */
    public function initiateLink(User $parent, string $studentNumber): void
    {
        $student = $this->findStudent($studentNumber);

        // Prevent duplicate link if student is already linked to this parent
        if ($parent->children()->where('student_id', $student->id)->exists()) {
            throw new Exception("This student ({$student->name}) is already linked to your account.");
        }

        $cooldown = Otp::getCooldownRemaining($student->id, 'parent_link');
        if ($cooldown > 0) {
            throw new Exception("Please wait {$cooldown} seconds before requesting another linking code.");
        }

        // Generate OTP for the student
        $otp = Otp::generate($student->id, 'parent_link');
        Otp::setCooldown($student->id, 'parent_link');

        // Send OTP to student email
        app(\App\Services\Email\EmailDeliveryService::class)->sendOtp($student->email, $otp->code, 'parent_link', $student->name);
    }

    /**
     * Verify the OTP and link the parent to the student.
     */
    public function verifyAndLink(User $parent, string $studentNumber, string $otpCode): User
    {
        $student = $this->findStudent($studentNumber);

        if ($parent->children()->where('student_id', $student->id)->exists()) {
            throw new Exception("This student ({$student->name}) is already linked to your account.");
        }

        $otpRecord = Otp::where('user_id', $student->id)
                        ->where('code', trim($otpCode))
                        ->where('purpose', 'parent_link')
                        ->first();

        if (!$otpRecord || !$otpRecord->isValid()) {
            Otp::recordFailedVerify($student->id, 'parent_link');
            throw new Exception("Invalid or expired OTP.");
        }

        // Mark OTP used & clear failure count
        $otpRecord->update(['used' => true]);
        Otp::clearFailedVerify($student->id, 'parent_link');

        // Link parent to student in pivot table
        DB::table('parent_student')->insertOrIgnore([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Keep student's guardian_email in sync if not set
        if (empty($student->guardian_email) && !empty($parent->email)) {
            $student->update(['guardian_email' => $parent->email]);
        }

        // Notify both parties
        $this->notifyPair(
            $parent,
            $student,
            "You have successfully linked your account to student {$student->name} ({$student->student_number}).",
            "Your parent/guardian {$parent->name} ({$parent->email}) has linked to your student profile."
        );

        return $student;
    }

    /**
     * Generate an instant 6-digit Link Code for a logged-in student.
     * Valid for 15 minutes. Bypasses email delays entirely.
     */
    public function generateStudentLinkCode(User $student): array
    {
        if (!$student->isStudent()) {
            throw new Exception("Only student accounts can generate parent link codes.");
        }

        // Invalidate previous link codes for this student
        Otp::invalidatePrevious($student->id, 'student_parent_invite');

        // Generate fresh 6-digit code with 15-minute expiration
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(15);

        $otp = Otp::create([
            'user_id'    => $student->id,
            'email'      => $student->email,
            'code'       => $code,
            'purpose'    => 'student_parent_invite',
            'used'       => false,
            'expires_at' => $expiresAt,
        ]);

        return [
            'code'               => $code,
            'expires_at'         => $expiresAt->toIso8601String(),
            'expires_in_seconds' => max(0, Carbon::now()->diffInSeconds($expiresAt, false)),
            'student_name'       => $student->name,
            'student_number'     => $student->student_number,
        ];
    }

    /**
     * Verify a 6-digit student link code and connect parent to the student.
     */
    public function verifyAndLinkWithStudentCode(User $parent, string $code): User
    {
        if (!$parent->isParent()) {
            throw new Exception("Only parent accounts can link to students using this code.");
        }

        $cleanCode = trim($code);
        if (strlen($cleanCode) !== 6 || !ctype_digit($cleanCode)) {
            throw new Exception("Please enter a valid 6-digit linking code.");
        }

        $otpRecord = Otp::where('code', $cleanCode)
                        ->where('purpose', 'student_parent_invite')
                        ->where('used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();

        if (!$otpRecord) {
            throw new Exception("Invalid or expired Link Code. Please ask the student to generate a fresh code.");
        }

        $student = User::find($otpRecord->user_id);
        if (!$student || !$student->isStudent()) {
            throw new Exception("Associated student account could not be found.");
        }

        if ($parent->children()->where('student_id', $student->id)->exists()) {
            throw new Exception("This student ({$student->name}) is already linked to your account.");
        }

        // Mark code as used
        $otpRecord->update(['used' => true]);

        // Link parent to student
        DB::table('parent_student')->insertOrIgnore([
            'parent_id'  => $parent->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Keep student's guardian_email in sync if not set
        if (empty($student->guardian_email) && !empty($parent->email)) {
            $student->update(['guardian_email' => $parent->email]);
        }

        // Send notifications
        $this->notifyPair(
            $parent,
            $student,
            "Successfully linked to student {$student->name} ({$student->student_number}) via instant link code.",
            "Your parent/guardian {$parent->name} ({$parent->email}) has connected to your account using your Link Code."
        );

        return $student;
    }

    /**
     * Unlink a parent and a student. Can be invoked by parent, student, or admin.
     */
    public function unlink(User $actor, User $parent, User $student): void
    {
        // Authorization check: Actor must be the parent, the student, or an admin
        if ($actor->id !== $parent->id && $actor->id !== $student->id && !$actor->isAdmin()) {
            throw new Exception("You are not authorized to unlink this connection.");
        }

        $deleted = DB::table('parent_student')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->delete();

        if (!$deleted) {
            throw new Exception("No active connection found between this parent and student.");
        }

        // Manage guardian_email fallback
        if ($student->guardian_email === $parent->email) {
            $remainingParent = $student->parents()->first();
            $student->update([
                'guardian_email' => $remainingParent ? $remainingParent->email : null,
            ]);
        }

        // Notify both parties about the unlink event
        if ($actor->id === $parent->id) {
            Notification::create([
                'user_id' => $student->id,
                'sent_by' => $parent->id,
                'type'    => 'parent_unlinked',
                'message' => "Your parent/guardian {$parent->name} has disconnected from your profile.",
                'is_read' => false,
            ]);
            Notification::create([
                'user_id' => $parent->id,
                'sent_by' => null,
                'type'    => 'student_unlinked',
                'message' => "You have unlinked student {$student->name} from your account.",
                'is_read' => false,
            ]);
        } elseif ($actor->id === $student->id) {
            Notification::create([
                'user_id' => $parent->id,
                'sent_by' => $student->id,
                'type'    => 'student_unlinked',
                'message' => "Student {$student->name} has removed your guardian link from their profile.",
                'is_read' => false,
            ]);
            Notification::create([
                'user_id' => $student->id,
                'sent_by' => null,
                'type'    => 'parent_unlinked',
                'message' => "You have removed {$parent->name} from your linked guardians.",
                'is_read' => false,
            ]);
        } else {
            // Admin action
            Notification::create([
                'user_id' => $student->id,
                'sent_by' => $actor->id,
                'type'    => 'parent_unlinked',
                'message' => "An administrator has removed {$parent->name} from your linked guardians.",
                'is_read' => false,
            ]);
            Notification::create([
                'user_id' => $parent->id,
                'sent_by' => $actor->id,
                'type'    => 'student_unlinked',
                'message' => "An administrator has unlinked student {$student->name} from your account.",
                'is_read' => false,
            ]);
        }
    }

    /**
     * Direct linking performed by an Administrator.
     */
    public function linkDirectlyByAdmin(User $admin, User $parent, User $student): void
    {
        if (!$admin->isAdmin()) {
            throw new Exception("Only administrators can perform administrative parent linking.");
        }
        if (!$parent->isParent()) {
            throw new Exception("The selected account is not registered as a parent.");
        }
        if (!$student->isStudent()) {
            throw new Exception("The selected account is not a student.");
        }

        if ($parent->children()->where('student_id', $student->id)->exists()) {
            throw new Exception("Parent {$parent->name} is already linked to {$student->name}.");
        }

        DB::table('parent_student')->insertOrIgnore([
            'parent_id'  => $parent->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (empty($student->guardian_email) && !empty($parent->email)) {
            $student->update(['guardian_email' => $parent->email]);
        }

        $this->notifyPair(
            $parent,
            $student,
            "An administrator linked your account to student {$student->name} ({$student->student_number}).",
            "An administrator linked parent/guardian {$parent->name} ({$parent->email}) to your profile."
        );
    }

    /**
     * Resolve a student account by student number or identifier.
     */
    public function findStudent(string $studentNumber): User
    {
        $student = User::where('student_number', trim($studentNumber))
                       ->where('role', 'student')
                       ->first();

        if (!$student) {
            $resolved = User::findByIdentifier(trim($studentNumber));
            if ($resolved && $resolved->role === 'student') {
                $student = $resolved;
            }
        }

        if (!$student) {
            throw new Exception("Student not found with that Student ID.");
        }

        return $student;
    }

    /**
     * Helper to send notifications to both parent and student.
     */
    protected function notifyPair(User $parent, User $student, string $parentMsg, string $studentMsg): void
    {
        try {
            Notification::create([
                'user_id' => $parent->id,
                'sent_by' => $student->id,
                'type'    => 'student_linked',
                'message' => $parentMsg,
                'is_read' => false,
            ]);

            Notification::create([
                'user_id' => $student->id,
                'sent_by' => $parent->id,
                'type'    => 'parent_linked',
                'message' => $studentMsg,
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch link notification: " . $e->getMessage());
        }
    }
}

