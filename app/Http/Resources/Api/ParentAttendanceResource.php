<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentAttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status,
            'excused' => (bool) $this->excused,
            'subject_code' => $this->subject_code,
            'subject_name' => $this->subject?->name ?? $this->subject_name,
            'time_in' => $this->time_in,
            'time_out' => $this->time_out,
            'method' => $this->method,
            'subject' => $this->subject ? [
                'code' => $this->subject->code,
                'name' => $this->subject->name,
            ] : null,
        ];
    }
}
