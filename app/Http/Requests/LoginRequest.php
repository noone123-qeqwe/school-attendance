<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $candidates = [
            $this->input('identifier'),
            $this->input('student_id'),
            $this->input('student_number'),
            $this->input('studentId'),
            $this->input('email'),
            $this->input('username'),
            $this->input('employee_id'),
            $this->input('employeeId'),
            $this->input('id'),
            $this->input('login'),
            $this->input('user'),
            $this->input('user_id'),
            $this->input('userId'),
        ];
        $id = '';
        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && trim((string)$candidate) !== '') {
                $id = trim((string)$candidate);
                break;
            }
        }

        $passCandidates = [
            $this->input('password'),
            $this->input('pass'),
            $this->input('pwd'),
            $this->input('user_password'),
        ];
        $pass = '';
        foreach ($passCandidates as $p) {
            if (is_scalar($p) && (string)$p !== '') {
                $pass = (string)$p;
                break;
            }
        }

        $this->merge([
            'identifier' => $id,
            'password'   => $pass,
        ]);
    }

    public function rules(): array
    {
        return [
            'identifier' => 'required|string',
            'password'   => 'required|string',
            'qr_token'   => 'nullable|string',
            'remember'   => 'nullable|boolean',
        ];
    }
}
