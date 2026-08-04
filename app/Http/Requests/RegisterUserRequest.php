<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Construct the full name server-side from individual name parts
     * so we don't rely on JavaScript to populate the hidden 'name' field.
     */
    protected function prepareForValidation(): void
    {
        // Inject role for admin routes if not provided
        if (!$this->has('role')) {
            if ($this->routeIs('admin.student.store')) {
                $this->merge(['role' => 'student']);
            } elseif ($this->routeIs('admin.teacher.store')) {
                $this->merge(['role' => 'teacher']);
            }
        }

        if ($this->has('first_name') && $this->has('surname')) {
            $name = trim($this->first_name);
            if ($this->filled('middle_name')) {
                $name .= ' ' . trim($this->middle_name);
            }
            $name .= ' ' . trim($this->surname);
            $this->merge(['name' => $name]);
        }
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
            $rules['semester']       = 'required|in:1,2,Summer';
            $rules['section']        = 'nullable|string|max:20';
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
