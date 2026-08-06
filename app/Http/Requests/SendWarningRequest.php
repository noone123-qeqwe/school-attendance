<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWarningRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'subject_code' => 'required|string',
            'type'         => 'required|in:warning_2,warning_3,warning_consecutive_3,custom',
            'message'      => 'nullable|string|max:500'
        ];
    }
}
