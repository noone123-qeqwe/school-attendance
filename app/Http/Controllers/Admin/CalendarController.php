<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRescheduleLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * Admin calendar view.
     */
    public function index()
    {
        $year = request('year', now()->year);
        $month = request('month', now()->month);
        
        // Optionally fetch stats for the view
        $events = Event::whereYear('date', $year)
                       ->whereMonth('date', $month)
                       ->where('status', '!=', 'cancelled')
                       ->get();
                       
        return view('admin.calendar', compact('year', 'month', 'events'));
    }

    /**
     * JSON feed for FullCalendar.
     */
    public function data(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');
        
        $query = Event::where('status', '!=', 'cancelled');
                      
        if ($start) {
            $query->where('date', '>=', Carbon::parse($start)->toDateString());
        }
        if ($end) {
            $query->where('date', '<=', Carbon::parse($end)->toDateString());
        }

        $events = $query->with('attendees')->get();

        $formatted = $events->map(function ($event) {
            $color = match($event->type) {
                'class' => '#3b82f6', // blue
                'exam' => '#ef4444', // red
                'meeting' => '#f59e0b', // amber
                'school_event' => '#8b5cf6', // purple
                'holiday' => '#10b981', // green
                default => '#6b7280'
            };

            return [
                'id' => $event->id,
                'title' => $event->name,
                'start' => $event->date->format('Y-m-d') . 'T' . $event->start_time->format('H:i:s'),
                'end' => $event->date->format('Y-m-d') . 'T' . $event->end_time->format('H:i:s'),
                'type' => $event->type,
                'location' => $event->location,
                'status' => $event->status,
                'editable' => true, // Admin can edit anything
                'color' => $color,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Create any event type including holiday.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:class,exam,meeting,school_event,holiday',
            'date' => 'required|date',
            'start_time' => 'required_unless:type,holiday',
            'end_time' => 'required_unless:type,holiday|after:start_time',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $event = Event::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'date' => $request->date,
            'start_time' => $request->type === 'holiday' ? '00:00:00' : $request->start_time,
            'end_time' => $request->type === 'holiday' ? '23:59:59' : $request->end_time,
            'location' => $request->type === 'holiday' ? null : $request->location,
            'status' => 'scheduled',
            'created_by' => Auth::id(),
            // For admin created events, organizer is null unless specified, but for meetings it might be them
            'organizer_id' => $request->type === 'meeting' ? Auth::id() : null,
        ]);

        return response()->json(['success' => true, 'event' => $event]);
    }

    /**
     * Edit/reschedule any event.
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:class,exam,meeting,school_event,holiday',
            'date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required',
            'end_time' => 'sometimes|required|after:start_time',
            'location' => 'nullable|string|max:255',
            'reschedule_reason' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $newDate = $request->has('date') ? Carbon::parse($request->date)->format('Y-m-d') : $event->date->format('Y-m-d');
        $newStart = $request->has('start_time') ? $request->start_time : $event->start_time->format('H:i:s');
        $newEnd = $request->has('end_time') ? $request->end_time : $event->end_time->format('H:i:s');
        $newLocation = $request->has('location') ? $request->location : $event->location;

        $timeChanged = ($newDate !== $event->date->format('Y-m-d')) || 
                       ($newStart !== $event->start_time->format('H:i:s')) || 
                       ($newEnd !== $event->end_time->format('H:i:s'));
                       
        $locationChanged = $newLocation !== $event->location;
        $isReschedule = ($timeChanged || $locationChanged) && $request->has('reschedule_reason');

        DB::beginTransaction();
        try {
            if ($isReschedule) {
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

                if ($event->status !== 'rescheduled') {
                    $event->original_start = $event->start_time;
                    $event->original_end = $event->end_time;
                    $event->original_location = $event->location;
                }

                $event->status = 'rescheduled';
                $event->rescheduled_by = Auth::id();
                $event->rescheduled_at = now();
                $event->reschedule_reason = $request->reschedule_reason;
            }

            if ($request->has('name')) $event->name = $request->name;
            if ($request->has('description')) $event->description = $request->description;
            if ($request->has('type')) {
                $event->type = $request->type;
                if ($event->type === 'holiday') {
                    $event->location = null;
                }
            }
            
            $event->date = $newDate;
            $event->start_time = $newStart;
            $event->end_time = $newEnd;
            if ($event->type !== 'holiday') {
                $event->location = $newLocation;
            }

            $event->save();
            DB::commit();

            return response()->json(['success' => true, 'event' => $event]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft cancel an event.
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->status = 'cancelled';
        $event->save();

        // TODO: Send EventCancelledNotification

        return response()->json(['success' => true]);
    }
}
