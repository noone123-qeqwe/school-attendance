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
        $status = $request->get('status', 'active');
        $query = User::query()->where('role', 'student')->with(['attendances', 'deviceBinding']);

        if ($status === 'active') {
            $query->whereNull('deleted_at')->where(function($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            });
        } elseif ($status === 'deactivated') {
            $query->withTrashed()->where(function($q) {
                $q->whereNotNull('deleted_at')->orWhere('is_active', false);
            });
        } else {
            $query->withTrashed();
        }

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
        return view('admin.students.index', compact('students', 'status'));
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

        $chunks = array_chunk($rows, 100);
        foreach ($chunks as $chunk) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($chunk, $nameIdx, $emailIdx, $idIdx, $courseIdx, $yearIdx, $semIdx, $sectionIdx, $passIdx, &$created, &$updated, &$skipped) {
                foreach ($chunk as $row) {
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
                        // Security check: Refuse to downgrade or overwrite existing non-student accounts (e.g. admins, teachers)
                        if ($existing->role !== 'student') {
                            $skipped++;
                            \Illuminate\Support\Facades\Log::warning("CSV Student Import skipped row for '{$email}': Account exists with elevated role '{$existing->role}'.");
                            continue;
                        }

                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update($attributes);
                        $updated++;
                    } else {
                        $attributes['password'] = Hash::make($plainPassword);
                        $attributes['must_change_password'] = true;
                        User::create($attributes);
                        $created++;
                    }
                }
            });
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
        $student->is_active = false;
        $student->save();
        $student->delete();
        activity()->performedOn($student)->causedBy(Auth::user())->log("Deleted student account: {$student->name} ({$student->student_number})");
        return redirect()->route('admin.students')->with('success', 'Student deleted.');
    }

    public function deactivateStudent(User $student)
    {
        abort_unless($student->role === 'student', 404);
        $student->is_active = false;
        $student->save();
        activity()->performedOn($student)->causedBy(Auth::user())->log("Deactivated student account: {$student->name} ({$student->student_number})");
        return back()->with('success', "Student {$student->name} has been deactivated.");
    }

    public function reactivateStudent($id)
    {
        $student = User::withTrashed()->findOrFail($id);
        abort_unless($student->role === 'student', 404);
        if ($student->trashed()) {
            $student->restore();
        }
        $student->is_active = true;
        $student->save();
        activity()->performedOn($student)->causedBy(Auth::user())->log("Reactivated student account: {$student->name} ({$student->student_number})");
        return back()->with('success', "Student {$student->name} has been reactivated.");
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
        $tab = $request->get('tab', 'summary'); // 'summary' or 'records'
        $subjects = Subject::orderBy('name')->get();

        if ($tab === 'records') {
            $recordQuery = Attendance::with(['user', 'subject', 'academicYear'])
                ->orderBy('date', 'desc')
                ->orderBy('time_in', 'desc');

            if ($request->filled('date'))       $recordQuery->whereDate('date', $request->date);
            if ($request->filled('date_from'))  $recordQuery->whereDate('date', '>=', $request->date_from);
            if ($request->filled('date_to'))    $recordQuery->whereDate('date', '<=', $request->date_to);
            if ($request->filled('status')) {
                if ($request->status === 'Excused') {
                    $recordQuery->where('excused', true);
                } else {
                    $recordQuery->where('status', $request->status);
                }
            }
            if ($request->filled('subject'))    $recordQuery->where('subject_code', $request->subject);
            if ($request->filled('course'))     $recordQuery->whereHas('user', fn($q) => $q->where('course', $request->course));
            if ($request->filled('year_level')) $recordQuery->whereHas('user', fn($q) => $q->where('year_level', $request->year_level));
            if ($request->filled('search')) {
                $s = $request->search;
                $recordQuery->whereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('student_number', 'like', "%{$s}%"));
            }

            $records = $recordQuery->paginate(30)->withQueryString();
            $logs = collect();

            return view('admin.attendance.index', compact('logs', 'records', 'subjects', 'tab'));
        }

        // Summary View by Class & Subject
        $query = Attendance::selectRaw('
            date, 
            subject_code, 
            COUNT(id) as total,
            SUM(CASE WHEN status = \'Present\' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = \'Late\' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = \'Absent\' AND (excused IS FALSE OR excused IS NULL) THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN excused IS TRUE THEN 1 ELSE 0 END) as excused_count
        ')
        ->with('subject')
        ->groupBy('date', 'subject_code')
        ->orderBy('date', 'desc');

        if ($request->filled('date'))    $query->whereDate('date', $request->date);
        if ($request->filled('subject')) $query->where('subject_code', $request->subject);
        
        $logs = $query->paginate(30)->withQueryString();
        $records = collect();

        return view('admin.attendance.index', compact('logs', 'records', 'subjects', 'tab'));
    }

    public function overrideAttendanceRecord(Request $request, Attendance $attendance)
    {
        $request->validate([
            'status' => 'required|in:Present,Late,Absent,Excused',
            'reason' => 'required|string|max:255',
        ]);

        $oldStatus = $attendance->status . ($attendance->excused ? ' (Excused)' : '');
        $newStatus = $request->status;
        $isExcused = $request->status === 'Excused';
        $actualStatus = $isExcused ? 'Absent' : $request->status;

        $attendance->update([
            'status' => $actualStatus,
            'excused' => $isExcused,
            'excuse_note' => $request->reason,
            'time_in' => in_array($actualStatus, ['Present', 'Late']) ? ($attendance->time_in ?? now()->format('H:i:s')) : null,
        ]);

        $studentName = $attendance->user->name ?? 'Student';
        activity()
            ->performedOn($attendance)
            ->causedBy(Auth::user())
            ->log("Admin override: Attendance ID #{$attendance->id} for {$studentName} changed from {$oldStatus} to {$newStatus}. Reason: {$request->reason}");

        return back()->with('success', "Attendance record updated to {$newStatus}.");
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
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,webp,gif,heic,heif|max:10240'
        ], [
            'profile_image.required' => 'Please select an image file to upload.',
            'profile_image.image' => 'The selected file must be a valid image.',
            'profile_image.mimes' => 'Allowed formats: JPG, JPEG, PNG, WEBP, GIF, HEIC.',
            'profile_image.max' => 'The profile image size must not exceed 10 MB.',
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
        return back()->with('success', 'Profile photo updated successfully!');
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
            'qr_expiry'                 => 'nullable|integer|min:5|max:300',
            'require_biometric'         => 'nullable|in:0,1',
            'admin_ip_whitelist'        => 'nullable|string',
        ]);

        $settingsToUpdate = [
            'school_name', 'school_short_name', 'school_subtitle', 'academic_year', 
            'current_semester', 'late_threshold', 'absent_threshold', 'warning_threshold', 
            'attendance_rate_threshold', 'gps_lat', 'gps_lng', 'gps_radius', 'qr_expiry', 'admin_ip_whitelist'
        ];

        foreach ($settingsToUpdate as $key) {
            if ($request->has($key)) {
                \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => $request->$key]);
            }
        }

        // Explicitly handle boolean toggles
        \App\Models\Setting::updateOrCreate(
            ['key' => 'require_biometric'],
            ['value' => $request->has('require_biometric') ? 1 : 0]
        );
        \App\Models\Setting::updateOrCreate(
            ['key' => 'auto_holiday'],
            ['value' => $request->has('auto_holiday') ? 1 : 0]
        );

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

        // Prevent non-super-admins from resetting admin accounts
        if ($user->isAdmin() && Auth::user()->admin_sub_role !== 'super_admin') {
            return back()->with('error', 'Only super admins can reset an administrator account\'s password.');
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

        $ip = $request->ip() ?: 'unknown';
        $cooldown = max(
            \App\Models\Otp::getCooldownRemaining($user->id, 'admin_login'),
            \App\Models\Otp::getCooldownRemaining($ip, 'admin_login')
        );
        if ($cooldown > 0) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => "Please wait {$cooldown} seconds before requesting a new verification code.",
                'cooldown' => $cooldown,
            ], 429);
        }

        $otp = \App\Models\Otp::generate($user->id, 'admin_login');
        \App\Models\Otp::setCooldown($user->id, 'admin_login');
        \App\Models\Otp::setCooldown($ip, 'admin_login');

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

        $admin->is_active = false;
        $admin->save();
        $admin->delete();
        activity()->performedOn($admin)->causedBy(Auth::user())->log("Deleted admin account: {$admin->name} ({$admin->email})");
        return redirect()->route('admin.admins')->with('success', 'Admin deleted.');
    }

    public function deactivateAdmin(User $admin)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        abort_if($admin->id === Auth::id(), 400, 'You cannot deactivate your own account.');
        $admin->is_active = false;
        $admin->save();
        activity()->performedOn($admin)->causedBy(Auth::user())->log("Deactivated admin account: {$admin->name} ({$admin->email})");
        return back()->with('success', "Admin {$admin->name} has been deactivated.");
    }

    public function reactivateAdmin($id)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        $admin = User::withTrashed()->findOrFail($id);
        abort_unless($admin->role === 'admin', 404);
        if ($admin->trashed()) {
            $admin->restore();
        }
        $admin->is_active = true;
        $admin->save();
        activity()->performedOn($admin)->causedBy(Auth::user())->log("Reactivated admin account: {$admin->name} ({$admin->email})");
        return back()->with('success', "Admin {$admin->name} has been reactivated.");
    }

    // ─────────────────────────────────────────
    // TEACHER MANAGEMENT
    // ─────────────────────────────────────────
    public function teachers(Request $request)
    {
        $status = $request->get('status', 'active');
        $query = User::query()->where('role', 'teacher');

        if ($status === 'active') {
            $query->whereNull('deleted_at')->where(function($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            });
        } elseif ($status === 'deactivated') {
            $query->withTrashed()->where(function($q) {
                $q->whereNotNull('deleted_at')->orWhere('is_active', false);
            });
        } else {
            $query->withTrashed();
        }

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

        $teachers = $query->orderBy('name')->paginate(50)->withQueryString();
        return view('admin.teachers.index', compact('teachers', 'status'));
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
                $attributes['must_change_password'] = true;
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
        $teacher->is_active = false;
        $teacher->save();
        $teacher->delete();
        activity()->performedOn($teacher)->causedBy(Auth::user())->log("Deleted teacher account: {$teacher->name} ({$teacher->employee_id})");
        return redirect()->route('admin.teachers')->with('success', 'Teacher deleted.');
    }

    public function deactivateTeacher(User $teacher)
    {
        abort_unless($teacher->role === 'teacher', 404);
        $teacher->is_active = false;
        $teacher->save();
        activity()->performedOn($teacher)->causedBy(Auth::user())->log("Deactivated teacher account: {$teacher->name} ({$teacher->employee_id})");
        return back()->with('success', "Teacher {$teacher->name} has been deactivated.");
    }

    public function reactivateTeacher($id)
    {
        $teacher = User::withTrashed()->findOrFail($id);
        abort_unless($teacher->role === 'teacher', 404);
        if ($teacher->trashed()) {
            $teacher->restore();
        }
        $teacher->is_active = true;
        $teacher->save();
        activity()->performedOn($teacher)->causedBy(Auth::user())->log("Reactivated teacher account: {$teacher->name} ({$teacher->employee_id})");
        return back()->with('success', "Teacher {$teacher->name} has been reactivated.");
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
                'teacher_notes' => 'Approved by Administrator ' . Auth::user()->name,
            ]);

            // Update original attendance
            $attendance = $correction->attendance;
            if ($attendance) {
                $attendance->status = 'Present';
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
            'admin_notes' => 'nullable|string|max:500'
        ]);

        $correction->update([
            'status' => 'rejected',
            'teacher_notes' => $request->admin_notes ?: 'Rejected by Administrator ' . Auth::user()->name,
        ]);

        return back()->with('success', 'Correction request rejected.');
    }

    public function bulkApproveCorrections(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No correction requests selected.');
        }

        $corrections = \App\Models\AttendanceCorrection::whereIn('id', $ids)->where('status', 'pending')->get();
        foreach ($corrections as $correction) {
            $correction->update([
                'status' => 'approved',
                'teacher_notes' => 'Bulk approved by Administrator',
            ]);
            if ($correction->attendance) {
                $correction->attendance->update(['status' => 'Present']);
            }
        }

        return back()->with('success', count($corrections) . ' correction requests approved.');
    }

    public function bulkRejectCorrections(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No correction requests selected.');
        }

        $count = \App\Models\AttendanceCorrection::whereIn('id', $ids)->where('status', 'pending')->update([
            'status' => 'rejected',
            'teacher_notes' => 'Bulk rejected by Administrator',
        ]);

        return back()->with('success', $count . ' correction requests rejected.');
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

    public function reports(Request $request, \App\Services\AttendanceReportService $reportService)
    {
        $report = $reportService->generate($request->all());
        $subjects = Subject::orderBy('name')->get();
        $courses = \App\Models\Course::orderBy('code')->get();

        return view('admin.reports.index', array_merge($report, compact('subjects', 'courses')));
    }

    public function exportReportsPdf(Request $request, \App\Services\AttendanceReportService $reportService)
    {
        $report = $reportService->generate($request->all());
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.pdf', $report);
        return $pdf->download('attendance-report-' . $report['type'] . '-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportReportsCsv(Request $request, \App\Services\AttendanceReportService $reportService)
    {
        $report = $reportService->generate($request->all());
        return $reportService->downloadCsv($report);
    }
}
