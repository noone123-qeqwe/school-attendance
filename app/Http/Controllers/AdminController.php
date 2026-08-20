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



    // ─────────────────────────────────────────
    // STUDENT MANAGEMENT
    // ─────────────────────────────────────────
    public function students(Request $request)
    {
        $query = User::where('role', 'student')->with(['attendances', 'deviceBinding']);

        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2
                ->where('name', 'like', "%{$q}%")
                ->orWhere('student_number', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
            );
        }

        $students = $query->orderBy('year_level')->orderBy('name')->paginate(100)->withQueryString();
        return view('admin.students.index', compact('students'));
     }

     public function createStudent()
     {
         return view('admin.students.create');
     }

     public function storeStudent(RegisterUserRequest $request)
     {
         // Verify OTP email (scoped for admin_student registration)
         $verifiedEmail = session('admin_reg_email_verified');
         if (!$verifiedEmail || strtolower($verifiedEmail) !== strtolower($request->email)) {
             return back()->withInput()->withErrors(['email' => 'Please verify the student\'s email address using the OTP sent to their email.']);
         }
         
         // Clear the session so it cannot be reused
         session()->forget('admin_reg_email_verified');

         $student = User::create([
             'name'           => trim($request->name),
             'student_number' => $request->student_number,
             'email'          => strtolower(trim($request->email)),
             'course'         => $request->course,
             'year_level'     => $request->year_level,
             'semester'       => $request->semester,
             'password'       => Hash::make($request->password),
             'role'           => 'student',
             'email_verified_at' => now(),
         ]);

         return redirect()->route('admin.students')
             ->with('success', "Student '{$student->name}' added successfully.");
     }

     public function searchStudents(Request $request)
    {
        $query = User::where('role', 'student')->with(['attendances', 'deviceBinding']);

        if ($request->filled('year_level')) $query->where('year_level', $request->year_level);
        if ($request->filled('semester'))   $query->where('semester', $request->semester);
        if ($request->filled('course'))     $query->where('course', $request->course);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2
                ->where('name', 'like', "%{$q}%")
                ->orWhere('student_number', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
            );
        }

        $students = $query->orderBy('year_level')->orderBy('name')->paginate(100)->withQueryString();

        return response()->json([
            'html'       => view('admin.students._rows', compact('students'))->render(),
            'total'      => $students->total(),
            'pagination' => $students->hasPages()
                ? view('pagination::bootstrap-4', ['paginator' => $students])->render()
                : null,
        ]);
    }

    public function previewStudentsPdf(Request $request)
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

        $students = $query->orderBy('year_level')->orderBy('name')->get();
        $filters = $request->only(['year_level', 'semester', 'course', 'search']);
        
        return view('admin.students.preview', compact('students', 'filters'));
    }

    public function exportStudentsPdf(Request $request)
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

        $students = $query->orderBy('year_level')->orderBy('name')->get();
        $filters = $request->only(['year_level', 'semester', 'course', 'search']);
        
        $pdf = Pdf::loadView('admin.students.pdf', compact('students', 'filters'));
        
        $filename = 'students-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportStudentsCsv(Request $request)
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

        $students = $query->orderBy('year_level')->orderBy('name')->get();
        
        $filename = 'students-' . now()->format('Y-m-d-H-i-s') . '.csv';
        return Excel::download(new \App\Exports\StudentsExport($students), $filename);
    }

    /**
     * Download a blank CSV template for student import.
     */
    public function downloadStudentTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="students_import_template.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // Write column headers
            fputcsv($handle, ['Name', 'Student Number', 'Email', 'Course', 'Year Level', 'Semester', 'Section', 'Password']);
            // Sample row
            fputcsv($handle, ['Juan Dela Cruz', '2024100', 'juan.delacruz@student.edu', 'BSCS', '1', '1', 'A', 'student123']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk import students from a CSV file.
     */
    public function importStudentsCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        if (empty($rows) || count($rows) < 2) {
            return back()->with('error', 'The uploaded CSV file is empty or missing data rows.');
        }

        // Normalize headers
        $rawHeaders = array_shift($rows);
        $headerMap = [];
        foreach ($rawHeaders as $idx => $headerName) {
            $clean = strtolower(trim(str_replace([' ', '_', '-'], '', $headerName)));
            $headerMap[$clean] = $idx;
        }

        // Expected keys matching variations
        $nameIdx     = $headerMap['name'] ?? null;
        $idIdx       = $headerMap['studentnumber'] ?? ($headerMap['studentid'] ?? ($headerMap['studentno'] ?? ($headerMap['id'] ?? null)));
        $emailIdx    = $headerMap['email'] ?? ($headerMap['emailaddress'] ?? null);
        $courseIdx   = $headerMap['course'] ?? ($headerMap['program'] ?? null);
        $yearIdx     = $headerMap['yearlevel'] ?? ($headerMap['year'] ?? null);
        $semIdx      = $headerMap['semester'] ?? ($headerMap['sem'] ?? null);
        $sectionIdx  = $headerMap['section'] ?? null;
        $passIdx     = $headerMap['password'] ?? null;

        if ($nameIdx === null || $emailIdx === null) {
            return back()->with('error', 'CSV header must include at least "Name" and "Email" columns.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name  = isset($row[$nameIdx]) ? trim($row[$nameIdx]) : '';
            $email = isset($row[$emailIdx]) ? strtolower(trim($row[$emailIdx])) : '';

            if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            $studentNumber = ($idIdx !== null && isset($row[$idIdx])) ? trim($row[$idIdx]) : null;
            $course        = ($courseIdx !== null && isset($row[$courseIdx])) ? trim($row[$courseIdx]) : null;
            $yearLevel     = ($yearIdx !== null && isset($row[$yearIdx]) && is_numeric(trim($row[$yearIdx]))) ? (int)trim($row[$yearIdx]) : null;
            $semester      = ($semIdx !== null && isset($row[$semIdx]) && is_numeric(trim($row[$semIdx]))) ? (int)trim($row[$semIdx]) : null;
            $section       = ($sectionIdx !== null && isset($row[$sectionIdx])) ? trim($row[$sectionIdx]) : 'A';
            $plainPassword = ($passIdx !== null && isset($row[$passIdx]) && !empty(trim($row[$passIdx]))) ? trim($row[$passIdx]) : 'student123';

            $existing = User::withTrashed()
                ->where(function ($q) use ($studentNumber, $email) {
                    if (!empty($studentNumber)) {
                        $q->where('student_number', $studentNumber);
                    }
                    $q->orWhere('email', $email);
                })
                ->first();

            $attributes = [
                'name'              => $name,
                'email'             => $email,
                'student_number'    => $studentNumber ?: null,
                'course'            => $course ?: null,
                'year_level'        => $yearLevel,
                'semester'          => $semester,
                'section'           => $section ?: 'A',
                'role'              => 'student',
                'email_verified_at' => now(),
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($attributes);
                $updated++;
            } else {
                $attributes['password'] = Hash::make($plainPassword);
                User::create($attributes);
                $created++;
            }
        }

        $message = "Import completed: {$created} new students added, {$updated} updated.";
        if ($skipped > 0) {
            $message .= " ({$skipped} invalid rows skipped).";
        }

        return redirect()->route('admin.students')->with('success', $message);
    }



    public function studentDetail(User $student)
    {
        abort_unless($student->role === 'student', 404);
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
        abort_unless($student->role === 'student', 404);
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
        abort_unless($student->role === 'student', 404);
        $admin = Auth::user();

        // Verify student is enrolled in this subject
        $isEnrolled = $student->getAllSubjects()->contains('code', $request->subject_code);

        if (!$isEnrolled) {
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



    public function editStudent(User $student)
    {
        abort_unless($student->role === 'student', 404);
        return view('admin.students.edit', compact('student'));
    }

    public function updateStudent(UpdateStudentRequest $request, User $student)
    {
        abort_unless($student->role === 'student', 404);
        $student->update($request->only('name','course','year_level','semester','email'));
        return redirect()->route('admin.students')->with('success', 'Student updated successfully.');
    }

    public function destroyStudent(User $student)
    {
        abort_unless($student->role === 'student', 404);
        $student->delete();
        return redirect()->route('admin.students')->with('success', 'Student deleted.');
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



    public function createSubject()
    {
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        return view('admin.subjects.create', compact('teachers'));
    }

    public function storeSubject(\App\Http\Requests\StoreSubjectRequest $request)
    {
        $data = $request->only([
            'code',
            'name',
            'year_level',
            'semester',
            'course',
            'units',
            'instructor_id',
            'section'
        ]);

        if (!empty($data['instructor_id'])) {
            $teacher = User::find($data['instructor_id']);
            if ($teacher) {
                $data['instructor'] = $teacher->name;
            }
        }

        $subject = Subject::create($data);

        $this->saveSubjectSchedules($subject, $request);

        return redirect()->route('admin.subjects')->with('success', 'Subject added.');
    }

    public function editSubject(Subject $subject)
    {
        $subject->load('schedules');
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        return view('admin.subjects.edit', compact('subject', 'teachers'));
    }




    
  public function updateSubject(\App\Http\Requests\UpdateSubjectRequest $request, Subject $subject)
{

    $data = $request->only([
        'code', 'name', 'year_level', 'semester', 'course', 'units', 'instructor_id', 'section',
    ]);
    if (!empty($data['instructor_id'])) {
        $teacher = User::find($data['instructor_id']);
        if ($teacher) {
            $data['instructor'] = $teacher->name;
        }
    }
    $subject->update($data);

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
            SUM(CASE WHEN status = \'Present\' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = \'Late\' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = \'Absent\' AND excused = 0 THEN 1 ELSE 0 END) as absent_count,
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

    public function exportAttendanceCsv(Request $request)
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
        $filename = 'attendance-logs-' . now()->format('Y-m-d-H-i-s') . '.csv';
        
        return Excel::download(new AttendanceExport($logs), $filename);
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
        // Verify the notification exists in the system (admin has system-wide access)
        abort_unless(auth()->user()->isAdmin(), 403);
        $notification->archive();
        return response()->json(['success' => true]);
    }

    public function unarchiveNotification(Notification $notification)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $notification->unarchive();
        return response()->json(['success' => true]);
    }

    public function deleteNotification(Notification $notification)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
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

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Prevent non-super-admins from resetting super_admin passwords
        if ($user->admin_sub_role === 'super_admin' && Auth::user()->admin_sub_role !== 'super_admin') {
            return back()->with('error', 'Only super admins can reset another super admin\'s password.');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Password for {$user->name} has been reset successfully.");
    }

    // ─────────────────────────────────────────
    // 2FA Admin Authentication
    // ─────────────────────────────────────────
    public function twoFactorForm()
    {
        $user = Auth::user();
        
        // Only send a new OTP if no valid unexpired one exists (prevents spam on page refresh)
        $hasValidOtp = \App\Models\Otp::where('user_id', $user->id)
            ->where('purpose', 'admin_login')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->exists();

        if (!$hasValidOtp) {
            $otp = \App\Models\Otp::generate($user->id, 'admin_login');
            
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpMail($otp->code, 'admin_login', $user->name));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send 2FA OTP: ' . $e->getMessage());
            }
        }
        
        return view('auth.admin_2fa');
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
    // ADMIN MANAGEMENT
    // ─────────────────────────────────────────
    public function admins(Request $request)
    {
        $query = User::where('role', 'admin');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $admins = $query->orderBy('name')->get();
        return view('admin.admins.index', compact('admins'));
    }

    public function createAdmin()
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        return view('admin.admins.create');
    }

    public function storeAdmin(Request $request)
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'phone'       => 'nullable|string|max:20',
            'department'  => 'nullable|string|max:255',
        ]);

        $admin = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => 'admin',
            'phone'       => $request->phone,
            'department'  => $request->department,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.admins')->with('success', 'Admin created successfully.');
    }

    public function editAdmin(User $admin)
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        if ($admin->role !== 'admin') {
            abort(404);
        }
        return view('admin.admins.edit', compact('admin'));
    }

    public function updateAdmin(Request $request, User $admin)
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        if ($admin->role !== 'admin') {
            abort(404);
        }

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $admin->id,
            'phone'      => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
        ]);

        $admin->update($request->only([
            'name', 'email', 'phone', 'department'
        ]));

        return redirect()->route('admin.admins')->with('success', 'Admin updated successfully.');
    }

    public function destroyAdmin(User $admin)
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        if ($admin->role !== 'admin') {
            abort(404);
        }
        
        // Prevent deleting oneself
        if ($admin->id === Auth::id()) {
            return redirect()->route('admin.admins')->with('error', 'You cannot delete your own account.');
        }

        $admin->delete();
        return redirect()->route('admin.admins')->with('success', 'Admin deleted.');
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

        if ($request->filled('department')) {
            $query->where('department', 'like', "%{$request->department}%");
        }

        $teachers = $query->orderBy('name')->get();
        return view('admin.teachers.index', compact('teachers'));
    }

    public function searchTeachers(Request $request)
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

        if ($request->filled('department')) {
            $query->where('department', 'like', "%{$request->department}%");
        }

        $teachers = $query->orderBy('name')->get();

        return response()->json([
            'html'  => view('admin.teachers._rows', compact('teachers'))->render(),
            'total' => $teachers->count(),
        ]);
    }

    /**
     * Download a blank CSV template for instructor import.
     */
    public function downloadTeacherTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="teachers_import_template.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // Write column headers
            fputcsv($handle, ['Name', 'Employee ID', 'Email', 'Department', 'Position', 'Specialization', 'Password']);
            // Sample row
            fputcsv($handle, ['Prof. Maria Santos', 'T-2024-005', 'maria.santos@school.edu', 'Computer Science', 'Instructor', 'Database Systems', 'teacher123']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk import teachers from CSV file.
     */
    public function importTeachersCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        if (empty($rows) || count($rows) < 2) {
            return back()->with('error', 'The uploaded CSV file is empty or missing data rows.');
        }

        // Normalize headers
        $rawHeaders = array_shift($rows);
        $headerMap = [];
        foreach ($rawHeaders as $idx => $headerName) {
            $clean = strtolower(trim(str_replace([' ', '_', '-'], '', $headerName)));
            $headerMap[$clean] = $idx;
        }

        $nameIdx     = $headerMap['name'] ?? null;
        $idIdx       = $headerMap['employeeid'] ?? ($headerMap['employee_id'] ?? ($headerMap['id'] ?? null));
        $emailIdx    = $headerMap['email'] ?? ($headerMap['emailaddress'] ?? null);
        $deptIdx     = $headerMap['department'] ?? ($headerMap['dept'] ?? null);
        $posIdx      = $headerMap['position'] ?? ($headerMap['pos'] ?? ($headerMap['title'] ?? null));
        $specIdx     = $headerMap['specialization'] ?? ($headerMap['spec'] ?? null);
        $passIdx     = $headerMap['password'] ?? null;

        if ($nameIdx === null || $emailIdx === null) {
            return back()->with('error', 'CSV header must include at least "Name" and "Email" columns.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name  = isset($row[$nameIdx]) ? trim($row[$nameIdx]) : '';
            $email = isset($row[$emailIdx]) ? strtolower(trim($row[$emailIdx])) : '';

            if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            $employeeId    = ($idIdx !== null && isset($row[$idIdx])) ? trim($row[$idIdx]) : null;
            $department    = ($deptIdx !== null && isset($row[$deptIdx])) ? trim($row[$deptIdx]) : null;
            $position      = ($posIdx !== null && isset($row[$posIdx])) ? trim($row[$posIdx]) : 'Instructor';
            $specialization= ($specIdx !== null && isset($row[$specIdx])) ? trim($row[$specIdx]) : null;
            $plainPassword = ($passIdx !== null && isset($row[$passIdx]) && !empty(trim($row[$passIdx]))) ? trim($row[$passIdx]) : 'teacher123';

            $existing = User::withTrashed()
                ->where(function ($q) use ($employeeId, $email) {
                    if (!empty($employeeId)) {
                        $q->where('employee_id', $employeeId);
                    }
                    $q->orWhere('email', $email);
                })
                ->first();

            $attributes = [
                'name'              => $name,
                'email'             => $email,
                'employee_id'       => $employeeId ?: null,
                'department'        => $department ?: null,
                'position'          => $position ?: 'Instructor',
                'specialization'    => $specialization ?: null,
                'role'              => 'teacher',
                'email_verified_at' => now(),
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($attributes);
                $updated++;
            } else {
                $attributes['password'] = Hash::make($plainPassword);
                User::create($attributes);
                $created++;
            }
        }

        $message = "Import completed: {$created} new instructors added, {$updated} updated.";
        if ($skipped > 0) {
            $message .= " ({$skipped} invalid rows skipped).";
        }

        return redirect()->route('admin.teachers')->with('success', $message);
    }

    public function exportTeachersCsv(Request $request)
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

        if ($request->filled('department')) {
            $query->where('department', 'like', "%{$request->department}%");
        }

        $teachers = $query->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="teachers-' . now()->format('Y-m-d-H-i-s') . '.csv"',
        ];

        $callback = function () use ($teachers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Employee ID', 'Email', 'Department', 'Position', 'Specialization']);
            foreach ($teachers as $teacher) {
                fputcsv($handle, [
                    $teacher->name,
                    $teacher->employee_id ?? 'N/A',
                    $teacher->email,
                    $teacher->department ?? 'General',
                    $teacher->position ?? 'Instructor',
                    $teacher->specialization ?? 'N/A',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportTeachersPdf(Request $request)
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

        if ($request->filled('department')) {
            $query->where('department', 'like', "%{$request->department}%");
        }

        $teachers = $query->orderBy('name')->get();
        $pdf = Pdf::loadView('admin.teachers.pdf', compact('teachers'));

        $filename = 'teachers-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        return $pdf->download($filename);
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



    // ─────────────────────────────────────────
    // ─────────────────────────────────────────
    // CORRECTION MANAGEMENT (Admin-level)
    // ─────────────────────────────────────────
    public function corrections(Request $request)
    {
        $query = \App\Models\AttendanceCorrection::with(['user', 'attendance.subject'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $corrections = $query->paginate(20)->withQueryString();
        return view('admin.corrections.index', compact('corrections'));
    }

    public function approveCorrection(\App\Models\AttendanceCorrection $correction)
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $correction->update([
                'status' => 'approved',
                'reviewed_by' => \Illuminate\Support\Facades\Auth::id(),
                'reviewed_at' => now(),
            ]);

            // Update original attendance
            $attendance = $correction->attendance;
            if ($attendance) {
                $attendance->status = $correction->requested_status;
                $attendance->save();
            }

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', 'Correction request approved.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Correction approval error: ' . $e->getMessage());
            return back()->with('error', 'Error approving correction.');
        }
    }

    public function rejectCorrection(Request $request, \App\Models\AttendanceCorrection $correction)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:500'
        ]);

        $correction->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes
        ]);

        return back()->with('success', 'Correction request rejected.');
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
        // Guard against re-processing already-handled excuses
        if ($excuseSubmission->status !== 'pending') {
            return back()->with('error', 'This excuse has already been ' . $excuseSubmission->status . '.');
        }

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
        // Guard against re-processing already-handled excuses
        if ($excuseSubmission->status !== 'pending') {
            return back()->with('error', 'This excuse has already been ' . $excuseSubmission->status . '.');
        }

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
