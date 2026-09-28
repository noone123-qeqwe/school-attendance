<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPeerBiometricAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student' && $this->user()->isActive();
    }

    public function rules(): array
    {
        return [
            'verification_id' => ['required', 'uuid'],
            'nonce' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'frames' => ['required', 'array', 'size:5'],
            'frames.*' => ['required', 'file', 'image', 'mimetypes:image/jpeg', 'max:350'],
            'student_id' => ['prohibited'],
            'voucher_student_id' => ['prohibited'],
            'session_id' => ['prohibited'],
            'match_score' => ['prohibited'],
            'liveness_passed' => ['prohibited'],
        ];
    }
}
