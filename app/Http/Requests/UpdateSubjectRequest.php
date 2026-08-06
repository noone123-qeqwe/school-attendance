<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject') ? $this->route('subject')->id : null;
        
        return [
            'code'       => 'required|string|unique:subjects,code,' . $subjectId,
            'name'       => 'required|string',
            'year_level' => 'required',
            'semester'   => 'required',
            'course'     => 'nullable|in:BSCS,BSIT,BSIS',
            'units'      => 'nullable|integer|min:1|max:6',
            'days'       => 'nullable|string|max:30',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i|after_or_equal:start_time',
            'instructor_id' => 'nullable|exists:users,id',
            'section'    => 'nullable|string',
        ];
    }
}
