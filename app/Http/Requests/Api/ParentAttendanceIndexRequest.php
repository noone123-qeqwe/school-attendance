<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParentAttendanceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The parent role and child ownership are checked by route middleware
        // and ParentApiController respectively.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'string', 'max:64'],
            'status' => ['sometimes', 'string', Rule::in(['Present', 'Late', 'Absent', 'Excused'])],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => [
                'sometimes',
                'date_format:Y-m-d',
                Rule::when($this->filled('date_from'), 'after_or_equal:date_from'),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
