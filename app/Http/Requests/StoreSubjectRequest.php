<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code'       => 'required|string|unique:subjects,code',
            'name'       => 'required|string',
            'year_level' => 'required',
            'semester'   => 'required',
            'course'     => 'nullable|in:BSCS,BSIT,BSIS', // for Admin
            'units'      => 'nullable|integer|min:1|max:6',
            'days'       => 'nullable|string|max:30',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i|after_or_equal:start_time',
            'instructor' => 'nullable|string',
            'section'    => 'nullable|string',
        ];
    }
}
