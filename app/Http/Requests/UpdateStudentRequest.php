<?php

namespace App\Http\Requests;

use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $student = $this->route('student');
        $this->merge([
            'course' => $this->input('course') ?: ($student?->course ?: 'BSCS'),
        ]);
    }

    public function rules()
    {
        $studentId = $this->route('student')->id;
        
        return [
            'name'       => 'required|string|max:255',
            'course'     => 'nullable|string',
            'year_level' => 'required|integer|between:1,4',
            'semester'   => 'required|integer',
            'section_id' => 'required|integer|exists:sections,id',
            'email'      => [
                'required',
                'email',
                'unique:users,email,' . $studentId,
                function ($attribute, $value, $fail) {
                    if (!\App\Services\OtpService::isValidGmailFormat((string) $value)) {
                        $fail('The email must be a valid Gmail address (e.g., username@gmail.com).');
                    }
                },
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('section_id')) {
                return;
            }

            $section = Section::with('course')->find($this->input('section_id'));
            $courseCode = trim((string) ($this->input('course') ?: 'BSCS'));

            if (!$section
                || (string) $section->year_level !== (string) $this->input('year_level')
                || strcasecmp(trim((string) $section->course?->code), $courseCode) !== 0) {
                $validator->errors()->add('section_id', 'Choose a section that matches the student’s course and year level.');
            }
        });
    }
}
