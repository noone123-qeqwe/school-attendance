<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Notification;
use App\Models\Holiday;
use App\Events\NotificationSent;
use App\Models\Warning;
use App\Http\Requests\RegisterUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\AnalyticsService;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Requests\SendWarningRequest;

class AdminController extends Controller
{
    // ─────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────
    public function index(Request $request, AnalyticsService $analyticsService)
    {
        $data = $analyticsService->getAdminDashboardData($request);
        return view('admin.dashboard', $data);
    }

    /**
     * AJAX endpoint for date-range filtered dashboard statistics.
     */
    public function dashboardStats(Request $request)
    {
        $range = $request->get('range', 'today');
        $startDate = today();
        $endDate = today();

        switch ($range) {
            case 'yesterday':
                $startDate = today()->subDay();
                $endDate = today()->subDay();
                break;
            case 'this_week':
                $startDate = today()->startOfWeek();
                $endDate = today();
                break;
            case 'this_month':
                $startDate = today()->startOfMonth();
                $endDate = today();
                break;
            case 'custom':
                $startDate = Carbon::parse($request->get('start_date', today()->toDateString()));
                $endDate = Carbon::parse($request->get('end_date', today()->toDateString()));
                break;
        }

        // Stats for selected range
        $stats = Attendance::selectRaw("status, COUNT(*) as total")
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = $stats->get('Present', 0);
        $late = $stats->get('Late', 0);
        $absent = $stats->get('Absent', 0);
        $total = $present + $late + $absent;
        $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

        // Chart data for the range
        $chartRaw = Attendance::selectRaw("DATE(date) as day, status, COUNT(*) as total")
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('day', 'status')
            ->get()
            ->groupBy('day');

        $chartLabels = [];
        $chartPresent = [];
        $chartLate = [];
        $chartAbsent = [];

        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $day) {
            $dayKey = $day->toDateString();
            $chartLabels[] = $day->format('M d');
            $dayData = $chartRaw->get($dayKey, collect());
            $chartPresent[] = $dayData->firstWhere('status', 'Present')->total ?? 0;
            $chartLate[] = $dayData->firstWhere('status', 'Late')->total ?? 0;
            $chartAbsent[] = $dayData->firstWhere('status', 'Absent')->total ?? 0;
        }

        return response()->json([
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'total' => $total,
            'rate' => $rate,
            'chart' => [
                'labels' => $chartLabels,
                'present' => $chartPresent,
                'late' => $chartLate,
                'absent' => $chartAbsent,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // EARLY WARNINGS
    // ─────────────────────────────────────────
    public function earlyWarnings()
    {
        $warnings = \App\Models\Warning::where('type', 'chronic_absenteeism')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('admin.early-warnings', compact('warnings'));
    }

    public function exportEarlyWarningsExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EarlyWarningsExport, 
            'early_warnings_' . now()->format('Y_m_d') . '.xlsx'
        );
    }

    // ─────────────────────────────────────────
    // STUDENT MANAGEMENT
    // ─────────────────────────────────────────
    public function students(Request $request)
    {
        $query = User::where('role', 'student')->with('attendances');

        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('student_number', 'like', '%'.$request->search.'%')
            );
        }

        $students = $query->orderBy('year_level')->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.students.index', compact('students'));
    }

    public function importStudents(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // Assuming first row is header
        
        if (!$header || count($header) < 5) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV format. Please ensure headers are correct (name, email, student_number, year_level, section).');
        }

        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) continue;
            
            // Expected columns: Name, Email, Student Number, Year Level, Section
            $name = trim($row[0]);
            $email = trim($row[1]);
            $studentNumber = trim($row[2]);
            $yearLevel = trim($row[3]);
            $section = trim($row[4]);
            
            if (empty($name) || empty($email)) continue;
            
            // Skip if user already exists
            if (User::where('email', $email)->orWhere('student_number', $studentNumber)->exists()) {
                continue;
            }

            User::create([
                'name' => $name,
                'email' => $email,
                'student_number' => $studentNumber,
                'year_level' => $yearLevel,
                'section' => $section,
                'role' => 'student',
                'password' => Hash::make('password'), // Default password
            ]);
            $imported++;
        }
        fclose($handle);

        return back()->with('success', "Successfully imported {$imported} students.");
    }

    public function studentDetail(User $student)
    {
        $records = Attendance::with('subject')
            ->where('user_id', $student->id)
            ->orderBy('date', 'desc')->get();

        $totalPresent = $records->where('status', 'Present')->count();
        $totalLate    = $records->where('status', 'Late')->count();
        $totalAbsent  = $records->where('status', 'Absent')->count();
        $total        = $records->count();
        $rate         = $total > 0 ? round((($totalPresent + $totalLate) / $total) * 100) : 0;

        return view('admin.student', compact('student','records','totalPresent','totalLate','totalAbsent','total','rate'));
    }

    public function resetDevice(User $student)
    {
        if ($student->deviceBinding) {
            $student->deviceBinding()->delete();
        }
        
        return back()->with('success', "Device binding for {$student->name} has been reset successfully.");
    }
    // ─────────────────────────────────────────
    // WARNING SYSTEM (Admin-scoped)
    // ─────────────────────────────────────────
    public function sendWarning(SendWarningRequest $request, User $student)
    {
        $admin = Auth::user();

        // Verify student has attendance in this subject
        $hasAttendance = Attendance::where('user_id', $student->id)
            ->where('subject_code', $request->subject_code)
            ->exists();

        if (!$hasAttendance) {
            return back()->with('error', 'This student is not enrolled in the selected subject.');
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
            'custom' => $request->message ?? "You have been flagged for excessive absences in {$subjectName}.",
        ];

        // Save warning record
        Warning::create([
            'user_id' => $student->id,
            'subject_code' => $request->subject_code,
            'type' => $request->type,
            'message' => $messages[$request->type],
            'sent_by' => $admin->id,
        ]);

        Notification::create([
            'user_id' => $student->id,
            'sent_by' => $admin->id,
            'type' => $request->type,
            'subject_code' => $request->subject_code,
            'message' => $messages[$request->type]
        ]);

        try {
            broadcast(new NotificationSent(
                userId: $student->id,
                message: $messages[$request->type],
                type: $request->type
            ));
        } catch (\Exception $e) {}

        return back()->with('success', "Warning sent to {$student->name}.");
    }

    public function createStudent()
    {
        return view('admin.students.create');
    }
    public function storeStudent(RegisterUserRequest $request)
    {
        try {

            // Check email verification
            $verifiedEmail = session('admin_reg_email_verified');
            if (!$verifiedEmail || $verifiedEmail !== $request->email) {
                return back()
                    ->withInput()
                    ->with('error', 'Please verify the student email address using OTP before adding the student.');
            }

            // Create the student
            $student = User::create([
                'name'           => trim($request->name),
                'student_number' => $request->student_number,
                'course'         => $request->course,
                'year_level'     => (int) $request->year_level,
                'semester'       => (int) $request->semester,
                'email'          => strtolower(trim($request->email)),
                'password'       => Hash::make($request->password),
                'role'           => 'student',
                'email_verified_at' => now(), // Mark as verified since admin verified it
            ]);

            // Send welcome notification to the student
            Notification::create([
                'user_id' => $student->id,
                'sent_by' => auth()->id(),
                'type' => 'welcome',
                'message' => "🎉 Welcome to the Smart Classroom Attendance System! Your account has been created successfully. You can now log in using your student number ({$student->student_number}) and the password provided by your administrator."
            ]);

            // Clear verification session data
            session()->forget([
                'admin_reg_email_verified', 
                'admin_reg_otp_code', 
                'admin_reg_otp_email', 
                'admin_reg_otp_expires'
            ]);

            return redirect()
                ->route('admin.students')
                ->with('success', "Student '{$student->name}' (#{$student->student_number}) has been added successfully.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Admin add student error: ' . $e->getMessage(), [
                'request_data' => $request->except('password'),
                'admin_id' => auth()->id()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'An error occurred while adding the student. Please try again.');
        }
    }

    public function editStudent(User $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    public function updateStudent(UpdateStudentRequest $request, User $student)
    {
        $student->update($request->only('name','course','year_level','semester','email'));
        return redirect()->route('admin.students')->with('success', 'Student updated successfully.');
    }

    public function destroyStudent(User $student)
    {
        $student->delete();
        return redirect()->route('admin.students')->with('success', 'Student deleted.');
    }

    public function exportStudentsPdf(Request $request)
    {
        $query = User::where('role', 'student')->with('attendances');

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
        
        $pdf = Pdf::loadView('admin.students.pdf', compact('students', 'filters'));
        
        $filename = 'students-list-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    public function previewStudentsPdf(Request $request)
    {
        $query = User::where('role', 'student')->with('attendances');

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
        
        return view('admin.students.preview', compact('students', 'filters'));
    }

    // ─────────────────────────────────────────
    // SUBJECT MANAGEMENT
    // ─────────────────────────────────────────
    public function subjects(Request $request)
    {
        $query = Subject::query()->with('schedules');
        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name','like','%'.$request->search.'%')
                ->orWhere('code','like','%'.$request->search.'%')
                ->orWhereHas('instructorUser', function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%');
                })
            );
        }

        $subjects = $query->orderBy('year_level')->orderBy('code')->paginate(20)->withQueryString();
        return view('admin.subjects.index', compact('subjects'));
    }

    public function exportSubjectsPdf(Request $request)
    {
        $query = Subject::query()->with('schedules');

        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name','like','%'.$request->search.'%')
                ->orWhere('code','like','%'.$request->search.'%')
                ->orWhereHas('instructorUser', function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%');
                })
            );
        }

        $subjects = $query->orderBy('year_level')->orderBy('semester')->orderBy('code')->get();
        $filters = $request->only(['search', 'course', 'year_level', 'semester']);

        $pdf = Pdf::loadView('admin.subjects.pdf', compact('subjects', 'filters'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 120,
                'defaultFont' => 'Helvetica',
            ]);

        return $pdf->download('subjects.pdf');
    }

    public function previewSubjectsPdf(Request $request)
    {
        $query = Subject::query()->with('schedules');

        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name','like','%'.$request->search.'%')
                ->orWhere('code','like','%'.$request->search.'%')
                ->orWhereHas('instructorUser', function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%');
                })
            );
        }

        $subjects = $query->orderBy('year_level')->orderBy('semester')->orderBy('code')->get();
        $filters = $request->only(['search', 'course', 'year_level', 'semester']);
        
        return view('admin.subjects.preview', compact('subjects', 'filters'));
    }

    public function createSubject()
    {
        return view('admin.subjects.create');
    }

    public function storeSubject(\App\Http\Requests\StoreSubjectRequest $request)
    {

        $subject = Subject::create($request->only([
            'code',
            'name',
            'year_level',
            'semester',
            'course',
            'units',
            'instructor_id',
            'section',
            'activeSessionCount',
            'scheduledToday',
            'classesCompleted',
            'classesPending',
            'systemAlerts',
            'totalDepartments',
            'totalCourses',
            'totalSections'
        ]));

        $this->saveSubjectSchedules($subject, $request);

        return redirect()->route('admin.subjects')->with('success', 'Subject added.');
    }

    public function editSubject(Subject $subject)
    {
        $subject->load('schedules');
        return view('admin.subjects.edit', compact('subject'));
    }




    
  public function updateSubject(\App\Http\Requests\UpdateSubjectRequest $request, Subject $subject)
{

    $subject->update($request->only([
        'code', 'name', 'year_level', 'semester', 'course', 'units', 'instructor_id', 'section',
    ]));

    $this->saveSubjectSchedules($subject, $request);

    return redirect()->route('admin.subjects')->with('success', 'Subject updated successfully.');
}

    public function destroySubject(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('admin.subjects')->with('success', 'Subject deleted.');
    }

    private function saveSubjectSchedules(Subject $subject, Request $request)
    {
        // Always delete existing schedules first
        $subject->schedules()->delete();

        $daysInput = $request->input('days');
        
        if ($daysInput && $request->filled('start_time') && $request->filled('end_time')) {
            $days = $this->parseScheduleDays($daysInput);
            
            foreach ($days as $day) {
                $subject->schedules()->create([
                    'day'        => $day,
                    'start_time' => $request->input('start_time'),
                    'end_time'   => $request->input('end_time'),
                ]);
            }
        }
    }

    private function parseScheduleDays(string $days): array
    {
        $days = trim(strtoupper($days));
        if ($days === '') {
            return [];
        }

        $result = [];
        $i = 0;
        
        while ($i < strlen($days)) {
            // Check for TH first (two characters)
            if ($i < strlen($days) - 1 && substr($days, $i, 2) === 'TH') {
                $result[] = 'Thursday';
                $i += 2;
            }
            // Then check single characters
            elseif ($days[$i] === 'M') {
                $result[] = 'Monday';
                $i++;
            }
            elseif ($days[$i] === 'T') {
                $result[] = 'Tuesday';
                $i++;
            }
            elseif ($days[$i] === 'W') {
                $result[] = 'Wednesday';
                $i++;
            }
            elseif ($days[$i] === 'F') {
                $result[] = 'Friday';
                $i++;
            }
            elseif ($days[$i] === 'S') {
                $result[] = 'Saturday';
                $i++;
            }
            elseif ($days[$i] === 'U') {
                $result[] = 'Sunday';
                $i++;
            }
            else {
                // Skip unknown characters
                $i++;
            }
        }

        return array_values(array_unique($result));
    }

    // ─────────────────────────────────────────
    // ATTENDANCE MONITORING
    // ─────────────────────────────────────────
    public function attendanceLogs(Request $request)
    {
        $query = Attendance::selectRaw('
            date, 
            subject_code, 
            COUNT(id) as total,
            SUM(CASE WHEN status = "Present" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "Late" THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = "Absent" AND excused = 0 THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN excused = 1 THEN 1 ELSE 0 END) as excused_count
        ')
        ->with('subject')
        ->groupBy('date', 'subject_code')
        ->orderBy('date', 'desc');

        // Apply filters
        if ($request->filled('date'))         $query->whereDate('date', $request->date);
        if ($request->filled('subject'))      $query->where('subject_code', $request->subject);
        
        $logs     = $query->paginate(30)->withQueryString();
        $subjects = Subject::orderBy('name')->get();

        return view('admin.attendance.index', compact('logs','subjects'));
    }



    public function exportAttendancePdf(Request $request)
    {
        $query = Attendance::with(['user','subject'])->orderBy('date','desc');

        // Apply same filters as attendanceLogs
        if ($request->filled('date'))         $query->whereDate('date', $request->date);
        if ($request->filled('status')) {
            if ($request->status === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->where('status', $request->status)->where('excused', false);
            }
        }
        if ($request->filled('year_level'))   $query->whereHas('user', fn($q) => $q->where('year_level', $request->year_level));
        if ($request->filled('semester'))     $query->whereHas('user', fn($q) => $q->where('semester', $request->semester));
        if ($request->filled('course'))       $query->whereHas('user', fn($q) => $q->where('course', $request->course));
        if ($request->filled('subject'))      $query->where('subject_code', $request->subject);
        
        if ($request->filled('student_name')) {
            $search = $request->student_name;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $logs = $query->get();
        $filters = $request->only(['date', 'status', 'year_level', 'semester', 'course', 'subject', 'student_name']);
        
        $pdf = Pdf::loadView('admin.attendance.pdf', compact('logs', 'filters'));
        
        $filename = 'attendance-logs-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    public function previewAttendancePdf(Request $request)
    {
        $query = Attendance::with(['user','subject'])->orderBy('date','desc');

        // Apply same filters as attendanceLogs
        if ($request->filled('date'))         $query->whereDate('date', $request->date);
        if ($request->filled('status')) {
            if ($request->status === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->where('status', $request->status)->where('excused', false);
            }
        }
        if ($request->filled('year_level'))   $query->whereHas('user', fn($q) => $q->where('year_level', $request->year_level));
        if ($request->filled('semester'))     $query->whereHas('user', fn($q) => $q->where('semester', $request->semester));
        if ($request->filled('course'))       $query->whereHas('user', fn($q) => $q->where('course', $request->course));
        if ($request->filled('subject'))      $query->where('subject_code', $request->subject);
        
        if ($request->filled('student_name')) {
            $search = $request->student_name;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $logs = $query->get();
        $filters = $request->only(['date', 'status', 'year_level', 'semester', 'course', 'subject', 'student_name']);
        
        return view('admin.attendance.preview', compact('logs', 'filters'));
    }



    // ─────────────────────────────────────────
    // NOTIFICATIONS (System-wide monitoring only)
    // ─────────────────────────────────────────

    public function markAllNotificationsRead()
    {
        Notification::where('user_id', auth()->id())->where('is_read', false)->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function notifications(Request $request)
    {
        $query = Notification::with(['user', 'sender', 'subject'])->orderBy('created_at', 'desc');

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

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $notifications = $query->paginate(20)->withQueryString();
        $users = User::where('role', 'student')->orderBy('name')->get();

        return view('admin.notifications.index', compact('notifications', 'users'));
    }

    public function archiveNotification(Notification $notification)
    {
        $notification->archive();
        return response()->json(['success' => true]);
    }

    public function unarchiveNotification(Notification $notification)
    {
        $notification->unarchive();
        return response()->json(['success' => true]);
    }

    public function deleteNotification(Notification $notification)
    {
        $notification->delete();
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // PROFILE
    // ─────────────────────────────────────────
    public function profile()
    {
        $user = Auth::user();
        return view('admin.profile', compact('user'));
    }

    public function updateImage(Request $request)
    {
        $request->validate(['profile_image'=>'required|image|mimes:jpeg,png,jpg|max:2048']);
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

    public function settings()
    {
        return view('admin.settings');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'school_name'               => 'nullable|string|max:255',
            'school_short_name'         => 'nullable|string|max:50',
            'school_subtitle'           => 'nullable|string|max:255',
            'academic_year'             => 'nullable|string|max:20',
            'current_semester'          => 'nullable|string|max:20',
            'late_threshold'            => 'nullable|integer|min:1',
            'absent_threshold'          => 'nullable|integer|min:1',
            'warning_threshold'         => 'nullable|integer|min:1|max:10',
            'attendance_rate_threshold' => 'nullable|integer|min:1|max:100',
            'gps_lat'                   => 'nullable|numeric|between:-90,90',
            'gps_lng'                   => 'nullable|numeric|between:-180,180',
            'gps_radius'                => 'nullable|integer|min:10|max:1000',
            'admin_ip_whitelist'        => 'nullable|string',
        ]);

        $settingsToUpdate = [
            'school_name', 'school_short_name', 'school_subtitle', 'academic_year', 
            'current_semester', 'late_threshold', 'absent_threshold', 'warning_threshold', 
            'attendance_rate_threshold', 'gps_lat', 'gps_lng', 'gps_radius', 'admin_ip_whitelist'
        ];

        foreach ($settingsToUpdate as $key) {
            if ($request->has($key)) {
                \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => $request->$key]);
            }
        }

        return back()->with('success', 'System settings updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        Auth::user()->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password updated successfully!');
    }

    // ─────────────────────────────────────────
    // 2FA Admin Authentication
    // ─────────────────────────────────────────
    public function twoFactorForm()
    {
        return redirect()->route('admin.dashboard');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $user = Auth::user();
        if (!$user || !$user->isAdmin()) return redirect()->route('login');

        $otpRecord = \App\Models\Otp::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->where('purpose', 'admin_login')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
        }

        $otpRecord->update(['used' => true]);
        $request->session()->put('admin_2fa_verified', true);

        return redirect()->route('admin.dashboard')->with('success', 'Authentication successful.');
    }

    public function resendTwoFactor(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);

        $otp = \App\Models\Otp::generate($user->id, 'admin_login');
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp->code, 'admin_login', $user->name));
            return response()->json(['success' => true, 'message' => 'OTP has been resent to your email.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send OTP.'], 500);
        }
    }

    // ─────────────────────────────────────────
    // HOLIDAY CALENDAR MANAGEMENT
    // ─────────────────────────────────────────
    public function calendar(Request $request)
    {
        $admin = Auth::user();
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        // Get holidays for the current month
        $holidays = Holiday::active()
            ->forMonth($year, $month)
            ->orderBy('date')
            ->get();

        return view('admin.calendar', compact('holidays', 'year', 'month'));
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:national,local,school,no_class'
        ]);

        Holiday::create([
            'date' => $request->date,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'is_active' => true,
            'created_by' => Auth::id()
        ]);

        return redirect()->route('admin.calendar')->with('success', 'Holiday added successfully.');
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
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

        return redirect()->route('admin.calendar')->with('success', 'Holiday updated successfully.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $holiday->delete();
        return redirect()->route('admin.calendar')->with('success', 'Holiday removed successfully.');
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
                    'type_label' => $holiday->type_label,
                    'color' => $holiday->type_color
                ];
            });

        return response()->json($holidays);
    }

    /**
     * Export attendance logs as Excel/CSV.
     */
    public function exportAttendance(Request $request)
    {
        $filters = [
            'subject_code' => $request->subject_code,
            'date'         => $request->date,
            'status'       => $request->status,
        ];

        $filename = 'attendance_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new AttendanceExport($filters), $filename);
    }



    // ─────────────────────────────────────────
    // TEACHER MANAGEMENT
    // ─────────────────────────────────────────
    public function teachers(Request $request)
    {
        $query = User::where('role', 'teacher');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
            );
        }

        $teachers = $query->orderBy('name')->get();
        return view('admin.teachers.index', compact('teachers'));
    }

    public function createTeacher()
    {
        return view('admin.teachers.create');
    }

    public function storeTeacher(RegisterUserRequest $request)
    {

        $teacher = User::create([
            'name'        => trim($request->name),
            'employee_id' => $request->employee_id,
            'email'       => strtolower(trim($request->email)),
            'department'  => $request->department,
            'position'    => $request->position,
            'password'    => Hash::make($request->password),
            'role'        => 'teacher',
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.teachers')
            ->with('success', "Teacher '{$teacher->name}' added successfully.");
    }

    public function editTeacher(User $teacher)
    {
        abort_unless($teacher->role === 'teacher', 404);
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function updateTeacher(Request $request, User $teacher)
    {
        abort_unless($teacher->role === 'teacher', 404);

        $request->validate([
            'name'        => 'required|string|max:255',
            'employee_id' => 'required|string|max:50|unique:users,employee_id,' . $teacher->id,
            'email'       => 'required|email|unique:users,email,' . $teacher->id,
            'department'  => 'nullable|string|max:100',
            'position'    => 'nullable|string|max:100',
        ]);

        $teacher->update($request->only('name', 'employee_id', 'email', 'department', 'position'));

        return redirect()->route('admin.teachers')
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroyTeacher(User $teacher)
    {
        abort_unless($teacher->role === 'teacher', 404);
        $teacher->delete();
        return redirect()->route('admin.teachers')->with('success', 'Teacher deleted.');
    }

    public function exportTeachersPdf(Request $request)
    {
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $pdf = Pdf::loadView('admin.teachers.pdf', compact('teachers'));
        return $pdf->download('teachers-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportTeachersExcel(Request $request)
    {
        $filename = 'teachers_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new \App\Exports\TeacherExport(), $filename);
    }

    // ─────────────────────────────────────────
    // EXCUSE MANAGEMENT (Admin-level)
    // ─────────────────────────────────────────
    public function excuses(Request $request)
    {
        $query = \App\Models\ExcuseSubmission::with(['user', 'attendance.subject'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $excuses = $query->paginate(20)->withQueryString();
        return view('admin.excuses.index', compact('excuses'));
    }

    public function approveExcuse(\App\Models\ExcuseSubmission $excuseSubmission)
    {
        $excuseSubmission->update([
            'status'      => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        if ($excuseSubmission->attendance) {
            $excuseSubmission->attendance->update(['excused' => true]);
        }

        return back()->with('success', 'Excuse approved.');
    }

    public function rejectExcuse(\App\Models\ExcuseSubmission $excuseSubmission)
    {
        $excuseSubmission->update([
            'status'      => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Excuse rejected.');
    }

    public function bulkApproveExcuses(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:excuse_submissions,id']);

        $submissions = \App\Models\ExcuseSubmission::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->get();

        foreach ($submissions as $submission) {
            $submission->update([
                'status'      => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            if ($submission->attendance) {
                $submission->attendance->update(['excused' => true]);
            }
        }

        return back()->with('success', count($submissions) . ' excuse(s) approved.');
    }

    public function bulkRejectExcuses(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:excuse_submissions,id']);

        $count = \App\Models\ExcuseSubmission::whereIn('id', $request->ids)
            ->where('status', 'pending')
            ->update([
                'status'      => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

        return back()->with('success', $count . ' excuse(s) rejected.');
    }

}
