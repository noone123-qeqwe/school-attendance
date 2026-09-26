<?php

namespace App\Http\Requests;

use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;

class GenerateAttendanceQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('session');
        return $session instanceof AttendanceSession
            && (bool) $this->user()?->can('generateAssistantQr', $session);
    }

    public function rules(): array
    {
        return [];
    }
}
