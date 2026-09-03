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
        $id = $this->input('identifier')
            ?? $this->input('email')
            ?? $this->input('username')
            ?? $this->input('student_number')
            ?? $this->input('employee_id')
            ?? $this->input('user')
            ?? '';
        $pass = $this->input('password') ?? $this->input('pass') ?? '';

        $this->merge([
            'identifier' => is_string($id) ? trim($id) : '',
            'password'   => is_string($pass) ? (string) $pass : '',
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
