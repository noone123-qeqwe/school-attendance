<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Models\EventAttendee;
use App\Models\EventAttendeeGroup;
use App\Models\EventRescheduleLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * Instructor calendar view.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Pass any necessary data, like current year/month
        $year = request('year', now()->year);
        $month = request('month', now()->month);
        
        // We also need the teacher's classes for the "Notify all of this class" buttons
        $classes = $user->subjects; // Using the subjects relationship on User
        
        return view('instructor.calendar', compact('year', 'month', 'classes'));
    }

    /**
     * JSON feed for FullCalendar.
     */
    public function data(Request $request, \App\Services\CalendarService $calendarService)
    {
        return response()->json(
            $calendarService->getEventsForUser(
                Auth::user(),
                $request->query('start'),
                $request->query('end')
            )
        );
    }

    /**
     * Search invitees for the attendee picker.
     */
    public function searchInvitees(Request $request)
    {
        $q = $request->query('q');
        $role = $request->query('role'); // Student, Parent-Guardian, Instructor

        $query = User::query();

        if ($q) {
            $query->where(function($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($role) {
            if ($role === 'Student') {
                $query->where('role', 'student');
            } elseif ($role === 'Parent-Guardian') {
                $query->where('role', 'parent');
            } elseif ($role === 'Instructor') {
                $query->where('role', 'teacher');
            }
        }

        $results = $query->select('id', 'name', 'email', 'role', 'profile_image')
                         ->paginate(10);

        return response()->json($results);
    }

    /**
     * Create a meeting event with attendees and bulk groups.
     */
    public function storeMeeting(Request $request)
    {
        $this->authorize('create', [Event::class, 'meeting']);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'location' => 'nullable|string|max:255',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'exists:users,id',
            'attendee_groups' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $event = Event::create([
                'name' => $request->name,
                'description' => $request->description,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'type' => 'meeting',
                'status' => 'scheduled',
                'location' => $request->location,
                'organizer_id' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            $userIdsToInvite = [];

            // Add manually selected attendees
            if ($request->has('attendee_ids')) {
                foreach ($request->attendee_ids as $id) {
                    $userIdsToInvite[] = $id;
                }
            }

            // Process bulk groups
            if ($request->has('attendee_groups')) {
                foreach ($request->attendee_groups as $group) {
                    // group structure expected: ['group_type' => 'class_parents', 'class_id' => 1]
                    $type = $group['group_type'];
                    $classId = $group['class_id'];

                    EventAttendeeGroup::create([
                        'event_id' => $event->id,
                        'group_type' => $type,
                        'class_id' => $classId
                    ]);

                    // Resolve users for the group right away (can also be done via Job)
                    $subject = \App\Models\Subject::find($classId);
                    if ($subject) {
                        $students = $subject->enrolledStudents;
                        
                        if ($type === 'class_students') {
                            $userIdsToInvite = array_merge($userIdsToInvite, $students->pluck('id')->toArray());
                        } elseif ($type === 'class_parents') {
                            foreach ($students as $student) {
                                $userIdsToInvite = array_merge($userIdsToInvite, $student->parents->pluck('id')->toArray());
                            }
                        } elseif ($type === 'class_instructors') {
                            if ($subject->instructor_id) {
                                $userIdsToInvite[] = $subject->instructor_id;
                            }
                        }
                    }
                }
            }

            // Deduplicate and insert attendees
            $userIdsToInvite = array_unique($userIdsToInvite);
            
            // Exclude the organizer from being an invitee if they are already organizing (optional)
            // But usually we just add them
            
            $attendeeData = [];
            foreach ($userIdsToInvite as $uid) {
                $attendeeData[$uid] = ['response' => 'pending'];
            }
            
            $event->attendees()->syncWithoutDetaching($attendeeData);

            DB::commit();

            // Send MeetingInviteNotification to $userIdsToInvite
            foreach ($userIdsToInvite as $uid) {
                if ($uid !== Auth::id()) {
                    \App\Models\Notification::create([
                        'user_id' => $uid,
                        'sent_by' => Auth::id(),
                        'type' => 'calendar_invite',
                        'message' => "You have been invited to a meeting: {$event->name} on {$event->date->format('M d, Y')} at {$event->start_time->format('h:i A')}.",
                    ]);
                }
            }

            return response()->json(['success' => true, 'event' => $event]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reschedule an event (time and/or location).
     */
    public function reschedule(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'date' => 'required_with:start_time|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'location' => 'nullable|string|max:255',
            'reschedule_reason' => 'required|string',
        ]);

        $newDate = $request->date ? Carbon::parse($request->date)->format('Y-m-d') : $event->date->format('Y-m-d');
        $newStart = $request->start_time ?: $event->start_time->format('H:i:s');
        $newEnd = $request->end_time ?: $event->end_time->format('H:i:s');
        $newLocation = $request->has('location') ? $request->location : $event->location;
        
        $timeChanged = ($newDate !== $event->date->format('Y-m-d')) || 
                       ($newStart !== $event->start_time->format('H:i:s')) || 
                       ($newEnd !== $event->end_time->format('H:i:s'));
                       
        $locationChanged = $newLocation !== $event->location;

        if (!$timeChanged && !$locationChanged) {
            return response()->json(['message' => 'No changes provided for reschedule.'], 422);
        }

        DB::beginTransaction();
        try {
            // Log the reschedule
            EventRescheduleLog::create([
                'event_id' => $event->id,
                'changed_by' => Auth::id(),
                'old_start' => $event->start_time,
                'old_end' => $event->end_time,
                'new_start' => $newStart,
                'new_end' => $newEnd,
                'old_location' => $event->location,
                'new_location' => $newLocation,
                'reason' => $request->reschedule_reason
            ]);

            // If it's the first reschedule, save original fields
            if ($event->status !== 'rescheduled') {
                $event->original_start = $event->start_time;
                $event->original_end = $event->end_time;
                $event->original_location = $event->location;
            }

            // Update event
            $event->date = $newDate;
            $event->start_time = $newStart;
            $event->end_time = $newEnd;
            $event->location = $newLocation;
            $event->status = 'rescheduled';
            $event->rescheduled_by = Auth::id();
            $event->rescheduled_at = now();
            $event->reschedule_reason = $request->reschedule_reason;
            $event->save();

            DB::commit();

            // Send EventRescheduledNotification to appropriate users
            $attendees = $event->attendees()->pluck('users.id');
            foreach ($attendees as $uid) {
                if ($uid !== Auth::id()) {
                    \App\Models\Notification::create([
                        'user_id' => $uid,
                        'sent_by' => Auth::id(),
                        'type' => 'calendar_reschedule',
                        'message' => "The event '{$event->name}' has been rescheduled to {$event->date->format('M d, Y')} at {$event->start_time->format('h:i A')}. Reason: {$request->reschedule_reason}",
                    ]);
                }
            }

            return response()->json(['success' => true, 'event' => $event]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
