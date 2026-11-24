<?php

namespace App\Http\Requests;

use App\Models\ClassStudentAssistant;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;

class ManageStudentAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('subject') ?? Subject::where('code', $this->route('subjectCode'))->first();

        return $subject instanceof Subject
            && ($this->user()?->can('manageAssignments', [ClassStudentAssistant::class, $subject]) ?? false);
    }

    public function rules(): array
    {
        return match ($this->method()) {
            'DELETE' => [],
            'PATCH' => ['can_submit_attendance' => ['required', 'boolean']],
            default => [
                'student_id' => ['required', 'integer', 'exists:users,id'],
                'starts_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'expires_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
                'can_submit_attendance' => ['sometimes', 'boolean'],
            ],
        };
    }
}
