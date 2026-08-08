<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Get formatted calendar events for a specific user and date range.
     */
    public function getEventsForUser(User $user, ?string $start = null, ?string $end = null): Collection
    {
        $events = collect();

        // If user is a parent, we optionally fetch their children's attendance
        // but for now, they want to see school events/holidays via Event model
        $eventQuery = Event::visibleTo($user)->where('status', '!=', 'cancelled');

        if ($start) {
            $eventQuery->where('date', '>=', Carbon::parse($start)->toDateString());
        }
        if ($end) {
            $eventQuery->where('date', '<=', Carbon::parse($end)->toDateString());
        }

        $fetchedEvents = $eventQuery->with('attendees')->get();

        $events = $fetchedEvents->map(function ($event) use ($user) {
            return $this->formatEvent($event, $user);
        });

        // If the user is a parent, also fetch and format attendance records for their children
        if ($user->isParent()) {
            $childIds = $user->children()->pluck('users.id');
            
            $attendanceQuery = Attendance::with(['subject', 'user'])
                ->whereIn('user_id', $childIds);
                
            if ($start) {
                $attendanceQuery->where('date', '>=', Carbon::parse($start)->toDateString());
            }
            if ($end) {
                $attendanceQuery->where('date', '<=', Carbon::parse($end)->toDateString());
            }
            
            $attendances = $attendanceQuery->get()->map(function ($attendance) {
                return $this->formatAttendanceAsEvent($attendance);
            });
            
            $events = $events->concat($attendances);
        }

        return $events;
    }

    /**
     * Map an Event to the FullCalendar format
     */
    private function formatEvent(Event $event, User $user): array
    {
        $color = match($event->type) {
            'class' => '#3b82f6', // blue
            'exam' => '#ef4444', // red
            'meeting' => '#f59e0b', // amber
            'school_event' => '#8b5cf6', // purple
            'holiday' => '#10b981', // green
            default => '#6b7280'
        };

        return [
            'id' => 'event_' . $event->id,
            'title' => $event->name,
            'start' => $event->date->format('Y-m-d') . 'T' . $event->start_time->format('H:i:s'),
            'end' => $event->date->format('Y-m-d') . 'T' . $event->end_time->format('H:i:s'),
            'type' => $event->type,
            'location' => $event->location,
            'status' => $event->status,
            'editable' => $user->isAdmin() || $user->can('update', $event),
            'color' => $color,
        ];
    }
    
    /**
     * Map an Attendance record to the FullCalendar format for Parents
     */
    private function formatAttendanceAsEvent(Attendance $attendance): array
    {
        $color = match($attendance->status) {
            'Present' => '#10b981', // green
            'Late' => '#f59e0b',    // amber
            'Absent' => '#ef4444',  // red
            default => '#6b7280'
        };
        
        $childName = $attendance->user->name ?? 'Child';
        $subjectCode = $attendance->subject_code;

        return [
            'id' => 'att_' . $attendance->id,
            'title' => "{$childName} - {$subjectCode} ({$attendance->status})",
            'start' => $attendance->date->format('Y-m-d') . 'T00:00:00',
            'end' => $attendance->date->format('Y-m-d') . 'T23:59:59',
            'type' => 'attendance',
            'status' => $attendance->status,
            'editable' => false,
            'color' => $color,
            'allDay' => true
        ];
    }
}
