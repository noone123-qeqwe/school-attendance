<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $studentId = $this->route('student')->id;
        
        return [
            'name'       => 'required|string|max:255',
            'course'     => 'required|string',
            'year_level' => 'required|integer',
            'semester'   => 'required|integer',
            'email'      => 'required|email|unique:users,email,' . $studentId,
        ];
    }
}
