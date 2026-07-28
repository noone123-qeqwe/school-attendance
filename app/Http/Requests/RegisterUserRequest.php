<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name'     => 'required|string|max:255',
            'role'     => 'required|in:student,teacher,parent',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];

        if ($this->role === 'student') {
            $rules['student_number'] = 'required|alpha_num|size:7|unique:users';
            $rules['course']         = 'required|string';
            $rules['year_level']     = 'required|integer|between:1,4';
            $rules['semester']       = 'required|integer|between:1,2';
        } elseif ($this->role === 'teacher') {
            $rules['employee_id']    = 'nullable|string|max:50|unique:users';
            $rules['department']     = 'nullable|string|max:255';
            $rules['position']       = 'nullable|string|max:255';
            $rules['specialization'] = 'nullable|string|max:500';

            if ($this->department === 'Other') {
                $rules['custom_department'] = 'required|string|max:255';
            }
        }

        return $rules;
    }
}
