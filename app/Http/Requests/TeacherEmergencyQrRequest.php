<?php

namespace App\Http\Requests;

use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;

class TeacherEmergencyQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('session');
        return $session instanceof AttendanceSession
            && $this->user()?->isTeacher()
            && $this->user()->isActive()
            && (int) $session->subject?->instructor_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [];
    }
}
