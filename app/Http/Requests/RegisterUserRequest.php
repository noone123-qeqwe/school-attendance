<?php

namespace App\Http\Requests;

use App\Services\OtpService;
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

        // Automatically set student course to BSCS if student role
        if ($this->input('role') === 'student' || $this->role === 'student' || $this->routeIs('admin.student.store')) {
            $this->merge([
                'course' => $this->input('course') ?: 'BSCS',
            ]);
            if ($this->filled('student_number')) {
                $this->merge([
                    'student_number' => trim((string)$this->student_number),
                ]);
            }
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
            'email'          => [
                'required',
                'string',
                'email',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (!OtpService::isValidGmailFormat((string) $value)) {
                        $fail('The email must be a valid Gmail address (e.g., username@gmail.com).');
                    }
                },
            ],
            'password'       => 'required|min:8|confirmed',
        ];

        // Strict role validation based on the route
        if ($this->routeIs('admin.teacher.store')) {
            $rules['role'] = 'required|in:teacher';
        } else {
            $rules['role'] = 'required|in:student,parent';
        }

        if ($this->role === 'student') {
            $rules['student_number'] = 'nullable|string|min:3|max:7|unique:users,student_number';
            $rules['course']         = 'nullable|string';
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

        if (!$this->routeIs('admin.*')) {
            $rules['terms'] = 'accepted';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $email = strtolower(trim((string) $this->input('email', '')));
            if ($email === '') {
                return;
            }

            if ($this->routeIs('register.submit')) {
                $verifiedEmail = strtolower(trim((string) session('reg_email_verified', '')));
                if (!$verifiedEmail || $verifiedEmail !== $email) {
                    $validator->errors()->add(
                        'email',
                        'This email address is unverified. Please verify your email with the verification code before completing registration.'
                    );
                }
            } elseif ($this->routeIs('admin.student.store')) {
                $verifiedEmail = strtolower(trim((string) session('admin_reg_email_verified', '')));
                if (!$verifiedEmail || $verifiedEmail !== $email) {
                    $validator->errors()->add(
                        'email',
                        'This email address is unverified. Please verify the student\'s email with the verification code before adding the student.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'terms.accepted'        => 'You must read and agree to the Privacy Notice and Terms & Conditions to create an account.',
            'student_number.unique' => 'This Student ID is already registered to an account.',
            'student_number.max'    => 'The Student ID may not be greater than 7 characters.',
            'student_number.min'    => 'The Student ID must be at least 3 characters.',
            'email.required'        => 'Please enter your Gmail address.',
            'email.email'           => 'Please enter a valid Gmail address (e.g., username@gmail.com).',
            'email.unique'          => 'This Gmail address is already registered. Please sign in or use another email.',
        ];
    }
}
