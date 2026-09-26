<?php

namespace App\Http\Requests;

use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;

class ExtendAttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('session');
        $user = $this->user();

        return $session instanceof AttendanceSession && $user && $user->isTeacher()
            && $user->isActive()
            && (int) $session->subject?->instructor_id === (int) $user->id;
    }

    public function rules(): array
    {
        return ['minutes' => ['required', 'integer', 'min:1', 'max:60']];
    }
}
