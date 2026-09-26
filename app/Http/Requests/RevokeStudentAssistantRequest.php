<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;

class RevokeStudentAssistantRequest extends FormRequest
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
        return [];
    }
}
