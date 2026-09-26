<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;

class AssignStudentAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher()
            && Subject::where('code', $this->route('subjectCode'))
                ->where('instructor_id', $this->user()->id)
                ->exists();
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'starts_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'expires_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
        ];
    }
}
