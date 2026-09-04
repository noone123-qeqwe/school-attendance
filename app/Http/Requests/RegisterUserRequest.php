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
            $middleName = trim((string)$this->middle_name);
            if (!$this->boolean('no_middle_name') && $this->filled('middle_name') && strtoupper($middleName) !== 'N/A') {
                $name .= ' ' . $middleName;
            }
            $name .= ' ' . trim($this->surname);
            $this->merge(['name' => $name]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name'           => 'required|string|max:255',
            'first_name'     => 'sometimes|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'no_middle_name' => 'nullable|boolean',
            'surname'        => 'sometimes|string|max:100',
            'email'          => 'required|email|unique:users',
            'password'       => 'required|min:8|confirmed',
        ];

        // Strict role validation based on the route
        if ($this->routeIs('admin.teacher.store')) {
            $rules['role'] = 'required|in:teacher';
        } else {
            $rules['role'] = 'required|in:student,parent';
        }

        if ($this->role === 'student') {
            $rules['student_number'] = 'required|alpha_num|size:7|unique:users';
            $rules['course']         = 'required|string';
            $rules['year_level']     = 'required|integer|between:1,4';
            $rules['semester']       = 'required|in:1,2,Summer';

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
