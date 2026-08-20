<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Holiday;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Get formatted calendar events for a specific user and date range.
     */
    public function getEventsForUser(User $user, ?string $start = null, ?string $end = null, ?int $childId = null): Collection
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

        // Merge auto-populated holidays from the dedicated holidays table
        $holidayQuery = Holiday::active();
        if ($start) {
            $holidayQuery->where('date', '>=', Carbon::parse($start)->toDateString());
        }
        if ($end) {
            $holidayQuery->where('date', '<=', Carbon::parse($end)->toDateString());
        }
        $fetchedHolidays = $holidayQuery->get();

        $holidayEvents = $fetchedHolidays->map(function ($holiday) {
            return $this->formatHoliday($holiday);
        });

        $events = $events->concat($holidayEvents);

        // If the user is a parent, also fetch and format attendance records for their children
        if ($user->isParent()) {
            $allChildIds = $user->children()->pluck('users.id');
            // Filter to specific child if requested, otherwise show all
            $childIds = $childId && $allChildIds->contains($childId)
                ? collect([$childId])
                : $allChildIds;
            
            $attendanceQuery = Attendance::with(['subject.schedules', 'subject.instructorUser', 'user'])
                ->whereIn('user_id', $childIds);
                
            if ($start) {
                $attendanceQuery->where('date', '>=', Carbon::parse($start)->toDateString());
            }
            if ($end) {
                $attendanceQuery->where('date', '<=', Carbon::parse($end)->toDateString());
            }
            
            $attendances = $attendanceQuery->get()->map(function ($attendance) use ($user) {
                return $this->formatAttendanceAsEvent($attendance, $user);
            });
            
            $events = $events->concat($attendances);
        }

        // If the user is a student, fetch their own attendance records
        if ($user->isStudent()) {
            $attendanceQuery = Attendance::with(['subject.schedules', 'subject.instructorUser'])
                ->where('user_id', $user->id);
                
            if ($start) {
                $attendanceQuery->where('date', '>=', Carbon::parse($start)->toDateString());
            }
            if ($end) {
                $attendanceQuery->where('date', '<=', Carbon::parse($end)->toDateString());
            }
            
            $attendances = $attendanceQuery->get()->map(function ($attendance) use ($user) {
                return $this->formatAttendanceAsEvent($attendance, $user);
            });
            
            $events = $events->concat($attendances);
        }

        return $events->unique(function ($item) {
            $datePart = isset($item['start']) ? substr((string)$item['start'], 0, 10) : '';
            $titlePart = strtolower(trim($item['title'] ?? ''));
            $typePart = $item['type'] ?? '';
            return "{$datePart}_{$titlePart}_{$typePart}";
        })->values();
    }

    /**
     * Map an Event to the FullCalendar format
     */
    private function formatEvent(Event $event, User $user): array
    {
        $color = match($event->type) {
            'class' => '#3b82f6', // blue
            'exam' => '#ec4899', // pink
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
            'description' => $event->description,
            'status' => $event->status,
            'editable' => $user->isAdmin() || $user->can('update', $event),
            'color' => $color,
        ];
    }

    /**
     * Map a Holiday record to the FullCalendar format
     */
    private function formatHoliday(Holiday $holiday): array
    {
        // Use type-specific colors; default to green for all holiday types
        $color = match($holiday->type) {
            'national' => '#10b981', // emerald green
            'local'    => '#34d399', // lighter green
            'school'   => '#6ee7b7', // very light green
            'no_class' => '#a7f3d0', // mint green
            default    => '#10b981',
        };

        return [
            'id'          => 'hol_' . $holiday->id,
            'title'       => $holiday->name,
            'start'       => $holiday->date->format('Y-m-d'),
            'end'         => $holiday->date->format('Y-m-d'),
            'allDay'      => true,
            'type'        => 'holiday',
            'location'    => null,
            'description' => $holiday->description,
            'status'      => 'scheduled',
            'editable'    => false,
            'color'       => $color,
        ];
    }
    
    /**
     * Map an Attendance record to the FullCalendar format
     */
    private function formatAttendanceAsEvent(Attendance $attendance, User $currentUser): array
    {
        $color = match($attendance->status) {
            'Present' => '#3b82f6', // blue
            'Late' => '#f97316',    // orange
            'Absent' => '#ef4444',  // red
            default => '#6b7280'
        };
        
        $subjectCode = $attendance->subject_code;
        $subject = $attendance->subject;
        $subjectName = $subject ? ($subject->name ?? $subjectCode) : $subjectCode;
        $instructorName = $subject && $subject->instructorUser ? $subject->instructorUser->name : ($subject->instructor ?? 'Unknown Instructor');
        
        $dayName = $attendance->date->format('l'); // e.g., 'Monday'
        $schedule = $subject ? $subject->schedules->where('day', $dayName)->first() : null;
        
        $timeString = '';
        if ($schedule) {
            $start = \Carbon\Carbon::parse($schedule->start_time)->format('g:i A');
            $end = \Carbon\Carbon::parse($schedule->end_time)->format('g:i A');
            $timeString = "{$start} - {$end}";
        }
        
        if ($currentUser->isParent()) {
            $childName = $attendance->user->name ?? 'Child';
            $title = "{$childName} - {$subjectCode}";
        } else {
            $title = "{$subjectCode}";
        }

        return [
            'id' => 'att_' . $attendance->id,
            'title' => $title,
            'start' => $attendance->date->format('Y-m-d') . 'T00:00:00',
            'end' => $attendance->date->format('Y-m-d') . 'T23:59:59',
            'type' => 'attendance',
            'status' => $attendance->status,
            'editable' => false,
            'color' => $color,
            'allDay' => true,
            'subject_name' => $subjectName,
            'instructor_name' => $instructorName,
            'time_string' => $timeString,
        ];
    }
}
