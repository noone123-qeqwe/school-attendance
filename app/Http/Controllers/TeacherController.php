<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Notification;
use App\Models\Holiday;
use App\Models\ExcuseSubmission;
use App\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Warning;
use App\Services\AnalyticsService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;
use App\Exports\AttendanceExport;

class TeacherController extends Controller
{
    // ─────────────────────────────────────────
    // TEACHER DASHBOARD
    // ─────────────────────────────────────────
    public function index(Request $request, AnalyticsService $analyticsService)
    {
        \Log::info("TeacherController@index START");
        $teacher = Auth::user();
        
        // ── Holiday Calendar Data ──
        $calYear = (int) $request->input('hcal_year', now()->year);
        $calMonth = (int) $request->input('hcal_month', now()->month);
        $calStart = Carbon::create($calYear, $calMonth, 1);

        $calendarHolidays = \App\Models\Holiday::active()
            ->whereBetween('date', [
                $calStart->copy()->subMonth()->startOfMonth()->toDateString(),
                $calStart->copy()->addMonth()->endOfMonth()->toDateString()
            ])
            ->orderBy('date')
            ->get();

        $calendarAnnouncements = \App\Models\Announcement::with('author')
            ->published()
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Build events map keyed by date
        $hcalEventsMap = [];
        foreach ($calendarHolidays as $hol) {
            $dateKey = $hol->date->format('Y-m-d');
            $hcalEventsMap[$dateKey][] = [
                'id' => $hol->id, 'type' => $hol->type, 'name' => $hol->name,
                'description' => $hol->description, 'date' => $dateKey,
                'date_formatted' => $hol->date->format('M j, Y'),
                'type_label' => $hol->type_label, 'source' => 'holiday',
            ];
        }
        foreach ($calendarAnnouncements as $ann) {
            $dateKey = $ann->scheduled_for ? $ann->scheduled_for->format('Y-m-d') : $ann->created_at->format('Y-m-d');
            $hcalEventsMap[$dateKey][] = [
                'id' => $ann->id, 'type' => 'announcement', 'name' => $ann->title,
                'description' => \Illuminate\Support\Str::limit($ann->content, 150),
                'date' => $dateKey, 'date_formatted' => Carbon::parse($dateKey)->format('M j, Y'),
                'type_label' => 'Announcement', 'source' => 'announcement',
                'author' => $ann->author->name ?? 'Admin',
            ];
        }

        // Build flat list for sidebar
        $hcalUpcoming = collect();
        foreach ($calendarHolidays->where('date', '>=', now()->toDateString())->take(10) as $hol) {
            $hcalUpcoming->push((object)[
                'id' => $hol->id, 'type' => $hol->type, 'name' => $hol->name,
                'description' => $hol->description, 'date' => $hol->date,
                'date_formatted' => $hol->date->format('M j, Y'),
                'type_label' => $hol->type_label, 'source' => 'holiday',
            ]);
        }
        foreach ($calendarAnnouncements->take(5) as $ann) {
            $annDate = $ann->scheduled_for ?? $ann->created_at;
            $hcalUpcoming->push((object)[
                'id' => $ann->id, 'type' => 'announcement', 'name' => $ann->title,
                'description' => \Illuminate\Support\Str::limit($ann->content, 100),
                'date' => $annDate, 'date_formatted' => $annDate->format('M j, Y'),
                'type_label' => 'Announcement', 'source' => 'announcement',
                'author' => $ann->author->name ?? 'Admin',
            ]);
        }
        $hcalUpcoming = $hcalUpcoming->sortBy('date')->values();

        \Log::info("TeacherController@index END");
        
        $data = $analyticsService->getTeacherDashboardData($request, $teacher);
        $data['calYear'] = $calYear;
        $data['calMonth'] = $calMonth;
        $data['hcalEventsMap'] = $hcalEventsMap;
        $data['hcalUpcoming'] = $hcalUpcoming;
        
        return view('teacher.dashboard', $data);
    }



    // ─────────────────────────────────────────
    // MY SUBJECTS
    // ─────────────────────────────────────────
    public function mySubjects(Request $request)
    {
        return redirect()->route('teacher.classroom.index');
    }

    public function createSubject()
    {
        return view('teacher.subjects.create');
    }

    public function storeSubject(Request $request)
    {
        $teacher = Auth::user();
        
        $request->validate([
            'code'       => 'required|string|unique:subjects,code',
            'name'       => 'required|string',
            'year_level' => 'required',
            'semester'   => 'required',
            'units'      => 'nullable|integer|min:1|max:6',
            'days'       => 'nullable|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'section'    => 'nullable|string',
        ]);

        $subject = Subject::create(array_merge($request->only([
            'code', 'name', 'year_level', 'semester', 'units', 'section'
        ]), [
            'instructor_id' => $teacher->id
        ]));

        $this->saveSubjectSchedules($subject, $request);

        return redirect()->route('teacher.classroom.index')->with('success', 'Subject added successfully.');
    }

    public function editSubject(Subject $subject)
    {
        $teacher = Auth::user();
        
        // Verify teacher owns this subject
        if ($subject->instructor_id !== $teacher->id) {
            abort(403, 'Unauthorized access to this subject.');
        }

        $subject->load('schedules');
        return view('teacher.subjects.edit', compact('subject'));
    }

    public function updateSubject(Request $request, Subject $subject)
    {
        $teacher = Auth::user();
        
        // Verify teacher owns this subject
        if ($subject->instructor_id !== $teacher->id) {
            abort(403, 'Unauthorized access to this subject.');
        }

        $request->merge([
            'start_time' => $request->start_time ?: null,
            'end_time'   => $request->end_time ?: null,
        ]);

        $request->validate([
            'code'       => 'required|string|unique:subjects,code,'.$subject->id,
            'name'       => 'required|string',
            'year_level' => 'required',
            'semester'   => 'required',
            'units'      => 'nullable|integer|min:1|max:6',
            'days'       => 'nullable|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'section'    => 'nullable|string',
        ]);

        $subject->update(array_merge($request->only([
            'code', 'name', 'year_level', 'semester', 'units', 'section'
        ]), [
            'instructor_id' => $teacher->id
        ]));

        $this->saveSubjectSchedules($subject, $request);

        return redirect()->route('teacher.classroom.index')->with('success', 'Subject updated successfully.');
    }

    public function destroySubject(Subject $subject)
    {
        $teacher = Auth::user();
        
        // Verify teacher owns this subject
        if ($subject->instructor_id !== $teacher->id) {
            abort(403, 'Unauthorized access to this subject.');
        }

        $subject->delete();
        return redirect()->route('teacher.classroom.index')->with('success', 'Subject deleted successfully.');
    }

    private function saveSubjectSchedules(Subject $subject, Request $request)
    {
        if ($request->has('days') || $request->has('start_time') || $request->has('end_time')) {
            // Clear existing schedules
            $subject->schedules()->delete();

            if ($request->filled('days') && $request->filled('start_time') && $request->filled('end_time')) {
                $daysString = strtoupper($request->days);
                $dayMap = [
                    'M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 
                    'TH' => 'Thursday', 'F' => 'Friday', 'S' => 'Saturday'
                ];

                $days = [];
                // Handle special cases like "TH" first
                if (strpos($daysString, 'TH') !== false) {
                    $days[] = 'Thursday';
                    $daysString = str_replace('TH', '', $daysString);
                }
                
                // Handle remaining single characters
                for ($i = 0; $i < strlen($daysString); $i++) {
                    $char = $daysString[$i];
                    if (isset($dayMap[$char])) {
                        $days[] = $dayMap[$char];
                    }
                }

                foreach (array_unique($days) as $day) {
                    $subject->schedules()->create([
                        'day' => $day,
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time,
                    ]);
                }
            }
        }
    }

    public function subjectStudents($subjectCode)
    {
        $teacher = Auth::user();
        $subject = Subject::where('code', $subjectCode)
            ->where('instructor_id', $teacher->id)
            ->firstOrFail();

        // Get students enrolled in this subject
        $students = $subject->enrolledStudents()
            ->with(['attendances' => function($q) use ($subjectCode) {
                $q->where('subject_code', $subjectCode);
            }])
            ->orderBy('name')
            ->get();

        return view('teacher.subject-students', compact('subject', 'students'));
    }

    // ─────────────────────────────────────────
    // ATTENDANCE MANAGEMENT
    // ─────────────────────────────────────────
    public function attendance(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');

        $query = Attendance::with(['user', 'subject'])
            ->whereIn('subject_code', $subjectCodes)
            ->orderBy('date', 'desc');

        // Apply filters
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('status')) {
            if ($request->status === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)])
                     ->where('excused', false);
            }
        }
        if ($request->filled('subject')) {
            $query->where('subject_code', $request->subject);
        }
        
        // Student name search
        if ($request->filled('student_name')) {
            $search = $request->student_name;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $attendanceRecords = $query->paginate(20)->withQueryString();

        return view('teacher.attendance', compact('attendanceRecords', 'teacherSubjects'));
    }

    public function excuseAttendance(Request $request, Attendance $attendance)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->pluck('code');

        // Verify teacher has access to this attendance record
        if (!$teacherSubjects->contains($attendance->subject_code)) {
            abort(403, 'Unauthorized access to this attendance record.');
        }

        $attendance->update([
            'excused' => true,
            'excuse_note' => $request->excuse_note
        ]);

        return back()->with('success', 'Attendance marked as excused.');
    }

    public function exportAttendancePdf(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');

        $query = Attendance::with(['user', 'subject'])
            ->whereIn('subject_code', $subjectCodes)
            ->orderBy('date', 'desc');

        // Apply same filters as attendance method
        if ($request->filled('date'))         $query->whereDate('date', $request->date);
        if ($request->filled('status')) {
            if ($request->status === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)])
                     ->where('excused', false);
            }
        }
        if ($request->filled('subject'))      $query->where('subject_code', $request->subject);
        
        if ($request->filled('student_name')) {
            $search = $request->student_name;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $attendanceRecords = $query->get();
        $filters = $request->only(['date', 'status', 'subject', 'student_name']);
        
        $options = [
            'isRemoteEnabled' => true,
            'dpi' => 150,
            'isHtml5ParserEnabled' => true,
        ];

        $pdf = Pdf::setOptions($options)
            ->setPaper('a4', 'portrait')
            ->loadView('teacher.attendance.pdf', compact('attendanceRecords', 'filters', 'teacher', 'teacherSubjects'));

        $filename = 'teacher-attendance-logs-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    public function previewAttendancePdf(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');

        $query = Attendance::with(['user', 'subject'])
            ->whereIn('subject_code', $subjectCodes)
            ->orderBy('date', 'desc');

        // Apply same filters as attendance method
        if ($request->filled('date'))         $query->whereDate('date', $request->date);
        if ($request->filled('status')) {
            if ($request->status === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)])
                     ->where('excused', false);
            }
        }
        if ($request->filled('subject'))      $query->where('subject_code', $request->subject);
        
        if ($request->filled('student_name')) {
            $search = $request->student_name;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $attendanceRecords = $query->get();
        $filters = $request->only(['date', 'status', 'subject', 'student_name']);
        
        return view('teacher.attendance.preview', compact('attendanceRecords', 'filters', 'teacher', 'teacherSubjects'));
    }

    public function overrideAttendance(Request $request, Attendance $attendance)
    {
        $teacher = Auth::user();
        
        $request->validate([
            'status' => 'required|in:Present,Late,Absent,Excused',
            'reason' => 'required|string|max:255',
        ]);

        // Verify the teacher owns the subject for this attendance record
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->pluck('code')
            ->toArray();

        if (!in_array($attendance->subject_code, $teacherSubjects)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $oldStatus = $attendance->status;
        $newStatus = $request->status;

        $attendance->status = $newStatus;
        if ($newStatus === 'Excused') {
            $attendance->excused = true;
        } else {
            $attendance->excused = false;
        }
        $attendance->save();

        activity()
            ->performedOn($attendance)
            ->causedBy($teacher)
            ->withProperties(['reason' => $request->reason, 'old_status' => $oldStatus, 'new_status' => $newStatus])
            ->log("Attendance status overridden from {$oldStatus} to {$newStatus}. Reason: {$request->reason}");

        return response()->json([
            'success' => true,
            'message' => 'Attendance status updated successfully.',
            'status' => $newStatus,
            'excused' => $attendance->excused
        ]);
    }

    // ─────────────────────────────────────────
    // STUDENT MANAGEMENT (Teacher-scoped)
    // ─────────────────────────────────────────


    public function students(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();

        // Get students who match the year/semester of teacher's subjects
        $subjectCodes = $teacherSubjects->pluck('code');
        
        // Get unique year_level and semester combinations from teacher's subjects
        $yearSemesters = $teacherSubjects->map(function($subject) {
            return [
                'year_level' => $subject->year_level,
                'semester' => $subject->semester,
                'course' => $subject->course
            ];
        })->unique()->values();

        $query = User::where('role', 'student')
            ->where(function($q) use ($yearSemesters) {
                foreach($yearSemesters as $ys) {
                    $q->orWhere(function($subQ) use ($ys) {
                        $subQ->where('year_level', $ys['year_level'])
                             ->where('semester', $ys['semester']);
                        if ($ys['course']) {
                            $subQ->where('course', $ys['course']);
                        }
                    });
                }
            })
            ->with(['attendances' => function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            }]);

        // Apply filters
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('course')) {
            $query->where('course', $request->course);
        }
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('student_number', 'like', '%'.$request->search.'%');
            });
        }
        $students = $query->orderBy('year_level')->orderBy('name')->get();

        return view('teacher.students.index', compact('students', 'teacherSubjects'));
    }

    public function storeStudentNote(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'note' => 'required|string|max:1000',
        ]);

        \App\Models\StudentNote::create([
            'teacher_id' => Auth::id(),
            'student_id' => $request->student_id,
            'note' => $request->note,
        ]);

        return back()->with('success', 'Note added successfully.');
    }

    public function destroyStudentNote(\App\Models\StudentNote $note)
    {
        if ($note->teacher_id !== Auth::id()) {
            abort(403);
        }

        $note->delete();
        return back()->with('success', 'Note deleted successfully.');
    }

    public function studentDetail(User $student)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');
        
        // Verify teacher has access to this student (student must match year/semester of teacher's subjects)
        $hasAccess = $teacherSubjects->filter(function($subject) use ($student) {
            return $subject->year_level == $student->year_level && 
                   $subject->semester == $student->semester &&
                   ($subject->course == null || $subject->course == $student->course);
        })->isNotEmpty();
            
        if (!$hasAccess) {
            abort(403, 'You do not have access to this student\'s records.');
        }
        
        // Only show attendance records for teacher's subjects
        $records = Attendance::with('subject')
            ->where('user_id', $student->id)
            ->whereIn('subject_code', $subjectCodes)
            ->orderBy('date', 'desc')->get();

        $totalPresent = $records->where('status', 'Present')->count();
        $totalLate    = $records->where('status', 'Late')->count();
        $totalAbsent  = $records->where('status', 'Absent')->count();
        $total        = $records->count();
        $rate         = $total > 0 ? round((($totalPresent + $totalLate) / $total) * 100) : 0;

        $notes = \App\Models\StudentNote::where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('teacher.student-detail', compact('student','records','totalPresent','totalLate','totalAbsent','total','rate','teacherSubjects', 'notes'));
    }

    public function exportStudentsPdf(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();

        $subjectCodes = $teacherSubjects->pluck('code');
        
        // Get students who match the year/semester of teacher's subjects
        $yearSemesters = $teacherSubjects->map(function($subject) {
            return [
                'year_level' => $subject->year_level,
                'semester' => $subject->semester,
                'course' => $subject->course
            ];
        })->unique()->values();

        $query = User::where('role', 'student')
            ->where(function($q) use ($yearSemesters) {
                foreach($yearSemesters as $ys) {
                    $q->orWhere(function($subQ) use ($ys) {
                        $subQ->where('year_level', $ys['year_level'])
                             ->where('semester', $ys['semester']);
                        if ($ys['course']) {
                            $subQ->where('course', $ys['course']);
                        }
                    });
                }
            })
            ->with(['attendances' => function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            }]);

        // Apply same filters as students method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);

        $students = $query->orderBy('year_level')->orderBy('course')->orderBy('name')->get();
        $filters = $request->only(['search', 'course', 'year_level', 'semester']);
        
        $options = [
            'isRemoteEnabled' => true,
            'dpi' => 150,
            'isHtml5ParserEnabled' => true,
        ];

        $pdf = Pdf::setOptions($options)
            ->setPaper('a4', 'portrait')
            ->loadView('teacher.students.pdf', compact('students', 'filters', 'teacher', 'teacherSubjects'));

        $filename = 'teacher-students-list-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    public function previewStudentsPdf(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();

        $subjectCodes = $teacherSubjects->pluck('code');
        
        // Get students who match the year/semester of teacher's subjects
        $yearSemesters = $teacherSubjects->map(function($subject) {
            return [
                'year_level' => $subject->year_level,
                'semester' => $subject->semester,
                'course' => $subject->course
            ];
        })->unique()->values();

        $query = User::where('role', 'student')
            ->where(function($q) use ($yearSemesters) {
                foreach($yearSemesters as $ys) {
                    $q->orWhere(function($subQ) use ($ys) {
                        $subQ->where('year_level', $ys['year_level'])
                             ->where('semester', $ys['semester']);
                        if ($ys['course']) {
                            $subQ->where('course', $ys['course']);
                        }
                    });
                }
            })
            ->with(['attendances' => function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            }]);

        // Apply same filters as students method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);

        $students = $query->orderBy('year_level')->orderBy('course')->orderBy('name')->get();
        $filters = $request->only(['search', 'course', 'year_level', 'semester']);
        
        return view('teacher.students.preview', compact('students', 'filters', 'teacher', 'teacherSubjects'));
    }

    // ─────────────────────────────────────────
    // REPORTS
    // ─────────────────────────────────────────
    public function reports(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');
        $type = $request->get('type', 'daily');
        $date = $request->get('date', today()->toDateString());
        $month = $request->get('month', today()->format('Y-m'));

        $data = [];

        if ($type === 'daily') {
            $data = Attendance::with(['user', 'subject'])
                ->whereIn('subject_code', $subjectCodes)
                ->whereDate('date', $date)
                ->orderBy('date', 'desc')
                ->get();

        } elseif ($type === 'monthly') {
            [$year, $mon] = explode('-', $month);
            $data = Attendance::with(['user', 'subject'])
                ->whereIn('subject_code', $subjectCodes)
                ->whereYear('date', $year)
                ->whereMonth('date', $mon)
                ->orderBy('date', 'desc')
                ->get();

        } elseif ($type === 'percentage') {
            $data = User::where('role', 'student')
                ->withCount([
                    'attendances as total_attendances' => function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes);
                    },
                    'attendances as present_count' => function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes)->whereIn('status', ['Present', 'Late']);
                    },
                    'attendances as absent_count' => function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes)->where('status', 'Absent');
                    },
                ])
                ->get()
                ->map(function($student) {
                    $total = $student->total_attendances;
                    $present = $student->present_count;
                    
                    return [
                        'student' => $student,
                        'total' => $total,
                        'present' => $present,
                        'absent' => $student->absent_count,
                        'rate' => $total > 0 ? round(($present / $total) * 100) : 0,
                    ];
                })
                ->sortByDesc('rate');
        }

        return view('teacher.reports', compact('type', 'date', 'month', 'data', 'teacherSubjects'));
    }

    public function exportPdf(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');
        $type = $request->get('type', 'daily');
        $date = $request->get('date', today()->toDateString());
        $month = $request->get('month', today()->format('Y-m'));

        $data = [];

        if ($type === 'daily') {
            $data = Attendance::with(['user', 'subject'])
                ->whereIn('subject_code', $subjectCodes)
                ->whereDate('date', $date)
                ->orderBy('date', 'desc')
                ->get();

        } elseif ($type === 'monthly') {
            [$year, $mon] = explode('-', $month);
            $data = Attendance::with(['user', 'subject'])
                ->whereIn('subject_code', $subjectCodes)
                ->whereYear('date', $year)
                ->whereMonth('date', $mon)
                ->orderBy('date', 'desc')
                ->get();

        } elseif ($type === 'percentage') {
            $data = User::where('role', 'student')
                ->withCount([
                    'attendances as total_attendances' => function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes);
                    },
                    'attendances as present_count' => function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes)->whereIn('status', ['Present', 'Late']);
                    },
                    'attendances as absent_count' => function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes)->where('status', 'Absent');
                    },
                ])
                ->get()
                ->map(function($student) {
                    $total = $student->total_attendances;
                    $present = $student->present_count;
                    
                    return [
                        'student' => $student,
                        'total' => $total,
                        'present' => $present,
                        'absent' => $student->absent_count,
                        'rate' => $total > 0 ? round(($present / $total) * 100) : 0,
                    ];
                })
                ->sortByDesc('rate');
        }

        $options = [
            'isRemoteEnabled' => true,
            'dpi' => 150,
            'isHtml5ParserEnabled' => true,
        ];

        $pdf = Pdf::setOptions($options)
            ->setPaper('a4', 'portrait')
            ->loadView('teacher.reports.pdf', compact('type', 'date', 'month', 'data', 'teacher'));
        
        $filename = match($type) {
            'daily' => "teacher-daily-report-{$date}.pdf",
            'monthly' => "teacher-monthly-report-{$month}.pdf",
            'percentage' => "teacher-attendance-percentage-" . now()->format('Y-m-d') . ".pdf",
        };

        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────
    // ABSENT REPORT (Teacher-scoped)
    // ─────────────────────────────────────────
    public function absentReport(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');
        $date = $request->filled('date') ? $request->date : today()->toDateString();
        
        // Only show absent records for teacher's subjects
        $absentRecords = Attendance::with(['user','subject'])
            ->where('status','Absent')
            ->whereDate('date',$date)
            ->whereIn('subject_code', $subjectCodes)
            ->orderBy('created_at','desc')
            ->get();

        if ($request->filled('year_level')) {
            $absentRecords = $absentRecords->filter(fn($r) => $r->user && $r->user->year_level == $request->year_level);
        }
        if ($request->filled('semester')) {
            $absentRecords = $absentRecords->filter(fn($r) => $r->user && $r->user->semester == $request->semester);
        }

        return view('teacher.absent-report', compact('absentRecords','date','teacherSubjects'));
    }

    // ─────────────────────────────────────────
    // NOTIFICATIONS (Teacher-scoped)
    // ─────────────────────────────────────────
    public function notifications(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');
        
        // Only show notifications sent by this teacher or related to their subjects
        $query = Notification::with(['user', 'sender', 'subject'])
            ->where(function($q) use ($teacher, $subjectCodes) {
                $q->where('sent_by', $teacher->id)
                  ->orWhereIn('subject_code', $subjectCodes);
            })
            ->orderBy('created_at', 'desc');

        // Filter by status (active/archived)
        if ($request->filled('status')) {
            if ($request->status === 'archived') {
                $query->archived();
            } else {
                $query->active();
            }
        } else {
            $query->active(); // Default to active notifications
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by user (only students in teacher's subjects)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $notifications = $query->paginate(20)->withQueryString();
        
        // Only show students who have attendance in teacher's subjects
        $studentIds = Attendance::whereIn('subject_code', $subjectCodes)
            ->distinct()
            ->pluck('user_id');
        $users = User::where('role', 'student')
            ->whereIn('id', $studentIds)
            ->orderBy('name')
            ->get();

        return view('teacher.notifications.index', compact('notifications', 'users', 'teacherSubjects'));
    }

    public function archiveNotification(Notification $notification)
    {
        $teacher = Auth::user();
        
        // Verify teacher has access to this notification
        if ($notification->sent_by !== $teacher->id) {
            $teacherSubjects = Subject::where('instructor_id', $teacher->id)
                ->pluck('code');
                
            if (!$teacherSubjects->contains($notification->subject_code)) {
                abort(403, 'Unauthorized access to this notification.');
            }
        }
        
        $notification->archive();
        return response()->json(['success' => true]);
    }

    public function unarchiveNotification(Notification $notification)
    {
        $teacher = Auth::user();
        
        // Verify teacher has access to this notification
        if ($notification->sent_by !== $teacher->id) {
            $teacherSubjects = Subject::where('instructor_id', $teacher->id)
                ->pluck('code');
                
            if (!$teacherSubjects->contains($notification->subject_code)) {
                abort(403, 'Unauthorized access to this notification.');
            }
        }
        
        $notification->unarchive();
        return response()->json(['success' => true]);
    }

    public function deleteNotification(Notification $notification)
    {
        $teacher = Auth::user();
        
        // Verify teacher has access to this notification
        if ($notification->sent_by !== $teacher->id) {
            $teacherSubjects = Subject::where('instructor_id', $teacher->id)
                ->pluck('code');
                
            if (!$teacherSubjects->contains($notification->subject_code)) {
                abort(403, 'Unauthorized access to this notification.');
            }
        }
        
        $notification->delete();
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // WARNING SYSTEM (Teacher-scoped)
    // ─────────────────────────────────────────
    public function sendWarning(Request $request, User $student)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');
        
        $request->validate([
            'subject_code'=>'required|string',
            'type'=>'required|in:warning_2,warning_3,warning_consecutive_3,custom',
            'message'=>'nullable|string|max:500'
        ]);

        // Verify teacher teaches this subject
        if (!$subjectCodes->contains($request->subject_code)) {
            abort(403, 'You do not have permission to send warnings for this subject.');
        }

        // Verify student has attendance in this subject
        $hasAttendance = Attendance::where('user_id', $student->id)
            ->where('subject_code', $request->subject_code)
            ->exists();
            
        if (!$hasAttendance) {
            return back()->with('error', 'This student is not enrolled in your subject.');
        }

        // Prevent duplicate warning
        $exists = Warning::where('user_id', $student->id)
            ->where('subject_code', $request->subject_code)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This student is already warned for this subject.');
        }

        $subject = Subject::where('code', $request->subject_code)->first();
        $subjectName = $subject ? $subject->name : $request->subject_code;

        $messages = [
            'warning_2' => "⚠️ Warning: You have been absent for 2 consecutive sessions in {$subjectName}.",
            'warning_3' => "🚨 Final Notice: You have been absent 3 or more times in {$subjectName}.",
            'warning_consecutive_3' => "🚨 URGENT: You have been absent for 3 CONSECUTIVE sessions in {$subjectName}. YOU HAVE TO GO TO THE OSAS TO GET THE READMISSION TO ENTER MY CLASS.",
            'custom'    => $request->message ?? "You have been flagged for excessive absences in {$subjectName}.",
        ];

        // Save warning record
        Warning::create([
            'user_id' => $student->id,
            'subject_code' => $request->subject_code,
            'type' => $request->type,
            'message' => $messages[$request->type],
            'sent_by' => $teacher->id,
        ]);

        Notification::create([
            'user_id'=>$student->id,
            'sent_by'=>$teacher->id,
            'type'=>$request->type,
            'subject_code'=>$request->subject_code,
            'message'=>$messages[$request->type]
        ]);

        try {
            broadcast(new NotificationSent(
                userId:  $student->id,
                message: $messages[$request->type],
                type:    $request->type
            ));
            
            // Broadcast to parents
            if ($student->parents) {
                foreach ($student->parents as $parent) {
                    broadcast(new \App\Events\LiveNotification(
                        userId: $parent->id,
                        title: 'Warning Issued',
                        message: $messages[$request->type],
                        type: 'error'
                    ))->toOthers();
                }
            }
        } catch (\Exception $e) {}

        return back()->with('success', "Warning sent to {$student->name}.");
    }

    // ─────────────────────────────────────────
    // AUTOMATIC CONSECUTIVE ABSENCE DETECTION
    // ─────────────────────────────────────────
    public function checkConsecutiveAbsences($studentId, $subjectCode)
    {
        // Get the last 3 attendance records for this student in this subject
        $recentAttendance = Attendance::where('user_id', $studentId)
            ->where('subject_code', $subjectCode)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();

        // Check if all 3 are absent and consecutive
        if ($recentAttendance->count() >= 3 && 
            $recentAttendance->every(fn($record) => $record->status === 'Absent')) {
            
            // Check if dates are consecutive (allowing for weekends/holidays)
            $dates = $recentAttendance->pluck('date')->sort();
            $isConsecutive = true;
            
            for ($i = 1; $i < $dates->count(); $i++) {
                $daysDiff = $dates[$i]->diffInDays($dates[$i-1]);
                // Allow up to 7 days difference (accounting for weekends)
                if ($daysDiff > 7) {
                    $isConsecutive = false;
                    break;
                }
            }

            if ($isConsecutive) {
                // Check if OSAS warning already sent
                $osasWarningExists = Warning::where('user_id', $studentId)
                    ->where('subject_code', $subjectCode)
                    ->where('type', 'warning_consecutive_3')
                    ->exists();

                if (!$osasWarningExists) {
                    $this->sendAutomaticOsasWarning($studentId, $subjectCode);
                }
            }
        }
    }

    private function sendAutomaticOsasWarning($studentId, $subjectCode)
    {
        $teacher = Auth::user();
        $student = User::find($studentId);
        $subject = Subject::where('code', $subjectCode)->first();
        $subjectName = $subject ? $subject->name : $subjectCode;

        $message = "🚨 URGENT: You have been absent for 3 CONSECUTIVE sessions in {$subjectName}. YOU HAVE TO GO TO THE OSAS TO GET THE READMISSION TO ENTER MY CLASS.";

        // Save warning record
        Warning::create([
            'user_id' => $studentId,
            'subject_code' => $subjectCode,
            'type' => 'warning_consecutive_3',
            'message' => $message,
            'sent_by' => $teacher->id,
        ]);

        // Create notification
        Notification::create([
            'user_id' => $studentId,
            'sent_by' => $teacher->id,
            'type' => 'warning_consecutive_3',
            'subject_code' => $subjectCode,
            'message' => $message
        ]);

        try {
            broadcast(new NotificationSent(
                userId: $studentId,
                message: $message,
                type: 'warning_consecutive_3'
            ));
        } catch (\Exception $e) {}
    }

    public function absenceSummary(User $student)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->pluck('code');
        
        // Only show absence summary for teacher's subjects
        $summary = Attendance::with('subject')
            ->where('user_id',$student->id)
            ->where('status','Absent')
            ->whereIn('subject_code', $teacherSubjects)
            ->get()
            ->groupBy('subject_code')
            ->map(fn($r) => [
                'subject_code'=>$r->first()->subject_code,
                'subject_name'=>$r->first()->subject->name??$r->first()->subject_code,
                'count'=>$r->count()
            ])
            ->values();
            
        return response()->json($summary);
    }

    // ─────────────────────────────────────────
    // EXCUSE REVIEWS (Teacher-scoped)
    // ─────────────────────────────────────────
    public function excuseReviews(Request $request)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->get();
        
        $subjectCodes = $teacherSubjects->pluck('code');
        
        // Get excuse submissions for teacher's subjects only
        $query = ExcuseSubmission::with(['user', 'attendance.subject', 'reviewer'])
            ->whereHas('attendance', function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            })
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by student
        if ($request->filled('student_id')) {
            $query->where('user_id', $request->student_id);
        }

        // Filter by subject
        if ($request->filled('subject')) {
            $query->whereHas('attendance', function($q) use ($request) {
                $q->where('subject_code', $request->subject);
            });
        }

        $excuseSubmissions = $query->paginate(20)->withQueryString();
        
        // Get students who have submitted excuses in teacher's subjects
        $students = User::where('role', 'student')
            ->whereHas('excuseSubmissions.attendance', function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            })
            ->orderBy('name')
            ->get();

        // Stats
        $totalSubmissions = ExcuseSubmission::whereHas('attendance', function($q) use ($subjectCodes) {
            $q->whereIn('subject_code', $subjectCodes);
        })->count();
        
        $pendingCount = ExcuseSubmission::where('status', 'pending')
            ->whereHas('attendance', function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            })->count();
            
        $approvedCount = ExcuseSubmission::where('status', 'approved')
            ->whereHas('attendance', function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            })->count();
            
        $rejectedCount = ExcuseSubmission::where('status', 'rejected')
            ->whereHas('attendance', function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            })->count();

        return view('teacher.excuse-reviews', compact(
            'excuseSubmissions', 
            'teacherSubjects', 
            'students', 
            'totalSubmissions', 
            'pendingCount', 
            'approvedCount', 
            'rejectedCount'
        ));
    }

    public function approveExcuse(Request $request, ExcuseSubmission $excuseSubmission)
    {
        try {
            // Step 1: Update excuse status
            $excuseSubmission->status = 'approved';
            $excuseSubmission->reviewed_at = now();
            $excuseSubmission->reviewed_by = Auth::id();
            $excuseSubmission->save();

            // Step 2: Update attendance if it exists
            $attendance = $excuseSubmission->attendance;
            if ($attendance) {
                $attendance->excused = true;
                $attendance->excuse_note = 'Approved by teacher';
                $attendance->save();
            }

            return response()->json([
                'success' => true, 
                'message' => 'Excuse approved successfully!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Excuse approval error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectExcuse(Request $request, ExcuseSubmission $excuseSubmission)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->pluck('code');

        // Verify teacher has access to this excuse submission
        if (!$teacherSubjects->contains($excuseSubmission->attendance->subject_code)) {
            abort(403, 'Unauthorized access to this excuse submission.');
        }

        $request->validate([
            'admin_notes' => 'required|string|max:500'
        ]);

        $excuseSubmission->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $teacher->id,
            'admin_notes' => $request->admin_notes
        ]);

        // Create notification for student
        try {
            $notificationData = [
                'user_id' => $excuseSubmission->user_id,
                'message' => "Your excuse for {$excuseSubmission->attendance->subject_code} on " . 
                             \Carbon\Carbon::parse($excuseSubmission->attendance->date)->format('M j, Y') . 
                             " has been rejected. Reason: " . $request->admin_notes,
                'type' => 'warning_2',
                'is_read' => false
            ];
            
            // Add sent_by and subject_code if they exist in the schema
            if (Schema::hasColumn('notifications', 'sent_by')) {
                $notificationData['sent_by'] = $teacher->id;
            }
            if (Schema::hasColumn('notifications', 'subject_code')) {
                $notificationData['subject_code'] = $excuseSubmission->attendance->subject_code;
            }
            
            \App\Models\Notification::create($notificationData);
        } catch (\Exception $notifError) {
            \Log::warning('Notification creation failed on reject: ' . $notifError->getMessage());
            // Continue even if notification fails
        }

        // Broadcast notification to student
        try {
            broadcast(new \App\Events\NotificationSent(
                userId: $excuseSubmission->user_id,
                message: "Your excuse for {$excuseSubmission->attendance->subject_code} on " . 
                         \Carbon\Carbon::parse($excuseSubmission->attendance->date)->format('M j, Y') . 
                         " has been rejected. Reason: " . $request->admin_notes,
                type: 'warning_2'
            ))->toOthers();
        } catch (\Exception $e) {
            // Broadcasting not available
        }

        return response()->json([
            'success' => true, 
            'message' => 'Excuse rejected with feedback.'
        ]);
    }

    public function viewExcuseDetail(ExcuseSubmission $excuseSubmission)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->pluck('code');

        // Verify teacher has access to this excuse submission
        if (!$teacherSubjects->contains($excuseSubmission->attendance->subject_code)) {
            abort(403, 'Unauthorized access to this excuse submission.');
        }

        $excuseSubmission->load(['user', 'attendance.subject', 'reviewer', 'comments.user']);

        return response()->json([
            'success' => true,
            'excuse' => $excuseSubmission
        ]);
    }

    public function storeExcuseComment(Request $request, ExcuseSubmission $excuseSubmission)
    {
        $teacher = Auth::user();
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)
            ->pluck('code');

        if (!$teacherSubjects->contains($excuseSubmission->attendance->subject_code)) {
            abort(403, 'Unauthorized access to this excuse submission.');
        }

        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $excuseSubmission->comments()->create([
            'user_id' => $teacher->id,
            'body' => $request->body,
        ]);

        return back()->with('success', 'Comment added.');
    }

    // ─────────────────────────────────────────
    // PROFILE
    // ─────────────────────────────────────────
    public function profile()
    {
        $teacher = Auth::user();
        return view('teacher.profile', compact('teacher'));
    }

    public function updateProfile(Request $request)
    {
        $teacher = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:500',
        ]);

        $teacher->update($request->only([
            'name', 'email', 'phone', 'department', 'position', 'specialization'
        ]));

        return redirect()->route('teacher.profile')->with('success', 'Profile updated successfully!');
    }

    public function updateImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($request->hasFile('profile_image')) {
            $user = Auth::user();

            // Delete old image if it exists in local storage
            if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profile_images', 'public');
            Auth::user()->update(['profile_image' => $path]);
        }

        return back()->with('success', 'Profile photo updated!');
    }

    // ─────────────────────────────────────────
    // HOLIDAY CALENDAR MANAGEMENT
    // ─────────────────────────────────────────
    public function calendar(Request $request)
    {
        $teacher = Auth::user();
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        // Get holidays for the current month
        $holidays = Holiday::active()
            ->forMonth($year, $month)
            ->orderBy('date')
            ->get();

        // Get all holidays for the year (for calendar display)
        $yearHolidays = Holiday::active()
            ->whereYear('date', $year)
            ->get()
            ->keyBy(function($holiday) {
                return $holiday->date->format('Y-m-d');
            });

        return view('teacher.calendar', compact('teacher', 'holidays', 'yearHolidays', 'year', 'month'));
    }

    public function storeHoliday(Request $request)
    {
        $teacher = Auth::user();

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:national,local,school,no_class'
        ]);

        // Check if holiday already exists for this date
        $existingHoliday = Holiday::where('date', $request->date)->first();
        
        if ($existingHoliday) {
            return back()->with('error', 'A holiday is already marked for this date: ' . $existingHoliday->name);
        }

        Holiday::create([
            'date' => $request->date,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'created_by' => $teacher->id
        ]);

        return back()->with('success', 'Holiday added successfully!');
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        $teacher = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:national,local,school,no_class'
        ]);

        $holiday->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type
        ]);

        return back()->with('success', 'Holiday updated successfully!');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday removed successfully!');
    }

    public function getCalendarData(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $holidays = Holiday::active()
            ->forMonth($year, $month)
            ->get()
            ->map(function($holiday) {
                return [
                    'id' => $holiday->id,
                    'date' => $holiday->date->format('Y-m-d'),
                    'name' => $holiday->name,
                    'description' => $holiday->description,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type_label,
                    'color' => $holiday->type_color
                ];
            });

        return response()->json($holidays);
    }

    // ─────────────────────────────────────────
    // TEACHER EXCUSES
    // ─────────────────────────────────────────
    public function myExcuses()
    {
        $teacher = Auth::user();
        
        $excuseSubmissions = \App\Models\ExcuseSubmission::with(['attendance.subject'])
            ->where('user_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('teacher.my_excuses.index', compact('excuseSubmissions'));
    }

    public function createExcuse()
    {
        $teacher = Auth::user();
        $subjects = \App\Models\Subject::where('instructor_id', $teacher->id)->get();
        return view('teacher.my_excuses.create', compact('subjects'));
    }

    public function storeExcuse(Request $request)
    {
        $request->validate([
            'subject_code' => 'required|exists:subjects,code',
            'date' => 'required|date',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $teacher = Auth::user();

        // Ensure teacher is the instructor for this subject
        if (!\App\Models\Subject::where('code', $request->subject_code)->where('instructor_id', $teacher->id)->exists()) {
            abort(403, 'You are not the instructor for this subject.');
        }

        // Get or create the attendance record
        $attendance = \App\Models\Attendance::firstOrCreate([
            'user_id' => $teacher->id,
            'subject_code' => $request->subject_code,
            'date' => $request->date,
        ], [
            'status' => 'Absent',
            'time_in' => null,
            'time_out' => null,
            'device_id' => null,
            'excused' => false
        ]);

        // If it already has an excuse, reject
        if ($attendance->excuseSubmission) {
            return redirect()->route('teacher.excuses')->with('error', 'Excuse already submitted for this class and date.');
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('excuses', 'public');
                $attachmentPaths[] = $path;
            }
        }

        \App\Models\ExcuseSubmission::create([
            'user_id' => $teacher->id,
            'attendance_id' => $attendance->id,
            'reason' => $request->reason,
            'description' => $request->description,
            'attachments' => empty($attachmentPaths) ? null : $attachmentPaths,
            'status' => 'pending',
        ]);

        return redirect()->route('teacher.excuses')->with('success', 'Excuse submitted successfully. It is pending administrative review.');
    }

    // ─────────────────────────────────────────
    // CLASSROOM MANAGER (UNIFIED)
    // ─────────────────────────────────────────
    public function classroomIndex(Request $request)
    {
        $teacher = Auth::user();
        $query = Subject::where('instructor_id', $teacher->id)
            ->with('schedules');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $subjects = $query->orderBy('year_level')->orderBy('code')->get();
        return view('teacher.classroom.index', compact('subjects'));
    }

    public function classroomShow(Request $request, $subjectCode)
    {
        $teacher = Auth::user();
        $subject = Subject::where('code', $subjectCode)
            ->where('instructor_id', $teacher->id)
            ->with('schedules')
            ->firstOrFail();

        // 1. Get Enrolled Students
        $students = User::where('role', 'student')
            ->where('year_level', $subject->year_level)
            ->where('semester', $subject->semester)
            ->when($subject->course, fn($q) => $q->where('course', $subject->course))
            ->with(['attendances' => function($q) use ($subjectCode) {
                $q->where('subject_code', $subjectCode);
            }])
            ->orderBy('name')
            ->get();

        // 2. Get Attendance History for this subject
        $attendanceRecords = Attendance::with('user')
            ->where('subject_code', $subjectCode)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(100) // Show recent
            ->get();

        return view('teacher.classroom.show', compact('subject', 'students', 'attendanceRecords'));
    }

    public function classroomStoreAttendance(Request $request, $subjectCode)
    {
        $teacher = Auth::user();
        $subject = Subject::where('code', $subjectCode)
            ->where('instructor_id', $teacher->id)
            ->firstOrFail();

        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array', 
        ]);

        $count = 0;
        foreach ($request->attendance as $userId => $status) {
            $statusNormalized = is_string($status) ? ucfirst(strtolower($status)) : $status;

            // When teacher marks a student as Present/Late, the record should not remain excused.
            $updateData = [
                'status' => $statusNormalized,
            ];

            if ($statusNormalized !== 'Absent') {
                $updateData['excused'] = false;
                $updateData['excuse_note'] = null;
            }

            Attendance::updateOrCreate(
                [
                    'user_id' => $userId,
                    'subject_code' => $subjectCode,
                    'date' => $request->date,
                ],
                [
                    // Note: we intentionally avoid clearing excused when status is Absent
                    // (teacher can approve an excuse later).
                    ...$updateData,
                ]
            );
            $count++;
        }

        return redirect()->route('teacher.classroom.show', $subjectCode)->with('success', "Attendance saved for {$count} student(s) on " . \Carbon\Carbon::parse($request->date)->format('M d, Y') . ".");
    }

    public function exportStudentsCsv(Request $request)
    {
        $teacher = Auth::user();
        $mySubjectCodes = $teacher->taughtSubjects()->pluck('code')->toArray();
        $query = User::where('role', 'student')
            ->whereHas('attendances', function ($q) use ($mySubjectCodes) {
                $q->whereIn('subject_code', $mySubjectCodes);
            });

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('student_number', 'LIKE', "%{$search}%");
            });
        }

        $students = $query->get();
        return Excel::download(new StudentsExport($students), 'students.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportAttendanceCsv(Request $request)
    {
        $teacher = Auth::user();
        $mySubjectCodes = $teacher->taughtSubjects()->pluck('code')->toArray();

        $filters = $request->only(['subject_code', 'date', 'status']);
        
        // Ensure teacher can only export their own subjects
        if (empty($filters['subject_code']) || !in_array($filters['subject_code'], $mySubjectCodes)) {
            // Default to first subject if not specified or invalid
            $filters['subject_code'] = $mySubjectCodes[0] ?? null;
        }

        return Excel::download(new AttendanceExport($filters), 'attendance.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}