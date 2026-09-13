<?php

namespace App\Services;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\DB;
use Exception;

class ParentService
{
    /**
     * Start the linking process by finding the student and sending an OTP to them.
     */
    public function initiateLink(User $parent, string $studentNumber): void
    {
        $student = $this->findStudent($studentNumber);

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
    public function verifyAndLink(User $parent, string $studentNumber, string $otpCode): void
    {
        $student = $this->findStudent($studentNumber);

        $otpRecord = Otp::where('user_id', $student->id)
                        ->where('code', $otpCode)
                        ->where('purpose', 'parent_link')
                        ->first();

        if (!$otpRecord || !$otpRecord->isValid()) {
            throw new Exception("Invalid or expired OTP.");
        }

        // Mark OTP used
        $otpRecord->update(['used' => true]);

        // Link parent to student
        DB::table('parent_student')->insertOrIgnore([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Resolve a student account by student number or identifier.
     */
    protected function findStudent(string $studentNumber): User
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
}
