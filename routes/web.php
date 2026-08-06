<?php

use App\Http\Controllers\PTController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageController;


// WebAuthn login — works for both guests and authenticated users
Route::post('/webauthn/login-options', [App\Http\Controllers\WebAuthnController::class, 'loginOptions'])->name('webauthn.login.options');
Route::post('/webauthn/login', [App\Http\Controllers\WebAuthnController::class, 'login'])->name('webauthn.login');


// Intro page should always show, even to authenticated users
Route::get('/', function () { return view('intro'); })->name('intro');

// Guest Routes (Public)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () { return view('auth.login'); })->name('login');
    Route::post('/login', [PTController::class, 'login'])->name('login.submit');
    Route::get('/csrf-token', function () { return response()->json(['token' => csrf_token()]); })->name('csrf.token');
    
    Route::get('/offline', function () { return view('offline'); })->name('offline');

    Route::get('/register', function () { return view('auth.register'); })->name('register');
    Route::post('/register', [PTController::class, 'register'])->name('register.submit');

    // Forgot Password (OTP flow)
    Route::get('/forgot-password', [App\Http\Controllers\OtpController::class, 'forgotForm'])->name('otp.forgot.form');
    Route::post('/forgot-password', [App\Http\Controllers\OtpController::class, 'sendForgotOtp'])->middleware('throttle:3,1')->name('otp.forgot.send');
    Route::get('/verify-otp', [App\Http\Controllers\OtpController::class, 'verifyForm'])->name('otp.verify.form');
    Route::post('/verify-otp', [App\Http\Controllers\OtpController::class, 'verifyOtp'])->name('otp.verify');
    Route::get('/reset-password', [App\Http\Controllers\OtpController::class, 'resetForm'])->name('otp.reset.form');
    Route::post('/reset-password', [App\Http\Controllers\OtpController::class, 'resetPassword'])->name('otp.reset');

    // Registration email OTP
    Route::post('/otp/send-register', [App\Http\Controllers\OtpController::class, 'sendRegisterOtp'])->middleware('throttle:3,1')->name('otp.register.send');
    Route::post('/otp/verify-register', [App\Http\Controllers\OtpController::class, 'verifyRegisterOtp'])->name('otp.register.verify');

    // Recovery code login
    Route::post('/recovery/login', [App\Http\Controllers\RecoveryCodeController::class, 'login'])->middleware('throttle:5,1')->name('recovery.login');
});

// Authenticated Routes (Protected) - Student Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [PTController::class, 'profile'])->name('profile');
    Route::post('/profile/image', [PTController::class, 'updateImage'])->name('profile.image.update');
    Route::post('/attendance/store', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/records', [AttendanceController::class, 'index'])->name('attendance.records');
    
    // Attendance Corrections
    Route::post('/corrections', [App\Http\Controllers\AttendanceCorrectionController::class, 'store'])->name('corrections.store');

    Route::post('/logout', [PTController::class, 'logout'])->name('logout');
    Route::get('/my-classes', [PTController::class, 'myClasses'])->name('student.classes');
    Route::get('/schedule', [App\Http\Controllers\Student\ScheduleController::class, 'index'])->name('student.schedule');

    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/settings', [App\Http\Controllers\HomeController::class, 'settings'])->name('settings');
    Route::post('/settings/update', [App\Http\Controllers\HomeController::class, 'update'])->name('settings.update');
    Route::post('/settings/preferences/update', [App\Http\Controllers\HomeController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::get('/notifications', [App\Http\Controllers\HomeController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [App\Http\Controllers\HomeController::class, 'markNotificationsRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\HomeController::class, 'deleteNotification'])->name('notifications.delete');
    Route::post('/notifications/{notification}/archive', [App\Http\Controllers\HomeController::class, 'archiveNotification'])->name('notifications.archive');
    Route::post('/notifications/{notification}/unarchive', [App\Http\Controllers\HomeController::class, 'unarchiveNotification'])->name('notifications.unarchive');

    // Excuse Submissions
    Route::get('/excuses', [App\Http\Controllers\HomeController::class, 'excuses'])->name('excuses');
    Route::get('/excuses/create/{attendance}', [App\Http\Controllers\HomeController::class, 'createExcuse'])->name('excuses.create');
    Route::post('/excuses', [App\Http\Controllers\HomeController::class, 'storeExcuse'])->name('excuses.store');

    // Change Password via OTP (authenticated)
    Route::post('/otp/send-change', [App\Http\Controllers\OtpController::class, 'sendChangeOtp'])->middleware('throttle:3,1')->name('otp.change.send');
    Route::post('/otp/change-password', [App\Http\Controllers\OtpController::class, 'changePassword'])->name('otp.change');



    // GPS Testing (for debugging)
    // Change Email via Email OTP (authenticated)
    Route::post('/otp/send-email-change', [App\Http\Controllers\OtpController::class, 'sendEmailChangeOtp'])->middleware('throttle:3,1')->name('otp.email.send');
    Route::post('/otp/change-email', [App\Http\Controllers\OtpController::class, 'changeEmail'])->name('otp.email.change');
});

// Local debug helper: generate students PDF as the first teacher (development only)
if (app()->environment('local') || config('app.debug')) {
    Route::get('/_debug/generate-students-pdf', function () {
        $teacher = \App\Models\User::where('role', 'teacher')->first();
        if (! $teacher) {
            return response('No teacher user found', 404);
        }

        \Illuminate\Support\Facades\Auth::login($teacher);

        $controller = new \App\Http\Controllers\TeacherController();
        return $controller->exportStudentsPdf(request());
    });

    Route::get('/test-teacher', function () {
        $teacher = \App\Models\User::where('role', 'teacher')->first();
        \Illuminate\Support\Facades\Auth::login($teacher);
        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'teacherSubjects' => collect(),
            'todayClasses' => collect(),
            'totalStudents' => 0,
            'totalPresent' => 0,
            'totalAbsent' => 0,
            'totalLate' => 0,
            'weeklyLabels' => [],
            'weeklyPresent' => [],
            'weeklyLate' => [],
            'weeklyAbsent' => [],
            'recentAttendance' => collect(),
            'pendingExcuses' => 0
        ]);
    });
}

// QR Scan (student) - allow public access so guests can scan and login through the QR flow
Route::get('/qr/scan/{token}', [App\Http\Controllers\QrAttendanceController::class, 'scan'])->name('qr.scan');

Route::middleware('auth')->group(function () {
    Route::post('/qr/confirm', [App\Http\Controllers\QrAttendanceController::class, 'confirm'])->name('qr.confirm');



    // WebAuthn (Fingerprint)
    Route::get('/webauthn/register-options', [App\Http\Controllers\WebAuthnController::class, 'registerOptions'])->name('webauthn.register.options');
    Route::post('/webauthn/register', [App\Http\Controllers\WebAuthnController::class, 'register'])->name('webauthn.register');
    Route::get('/webauthn/devices', [App\Http\Controllers\WebAuthnController::class, 'devices'])->name('webauthn.devices');
    Route::delete('/webauthn/device', [App\Http\Controllers\WebAuthnController::class, 'removeDevice'])->name('webauthn.remove');

    // WebAuthn QR verification
    Route::post('/qr/verify-options', [App\Http\Controllers\QrAttendanceController::class, 'verificationOptions'])->name('qr.verify.options');
    Route::post('/qr/verify-complete', [App\Http\Controllers\QrAttendanceController::class, 'completeVerification'])->name('qr.verify.complete');
});

// Teacher Routes (Teachers only)
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\TeacherController::class, 'index'])->name('dashboard');
    
    // Classroom (Unified Interface)
    Route::get('/classroom', [App\Http\Controllers\TeacherController::class, 'classroomIndex'])->name('classroom.index');
    Route::get('/classroom/{subjectCode}', [App\Http\Controllers\TeacherController::class, 'classroomShow'])->name('classroom.show');
    Route::post('/classroom/{subjectCode}/attendance', [App\Http\Controllers\TeacherController::class, 'classroomStoreAttendance'])->name('classroom.attendance.store');

    // Cover Class (Substitute Access)
    Route::get('/cover-class', [App\Http\Controllers\TeacherController::class, 'coverClassForm'])->name('cover.form');
    Route::get('/cover-class/subjects/{teacherId}', [App\Http\Controllers\TeacherController::class, 'getTeacherSubjects'])->name('cover.subjects');
    Route::post('/cover-class', [App\Http\Controllers\TeacherController::class, 'storeCoverClass'])->name('cover.store');

    // My Subjects - Full CRUD
    Route::get('/subjects', [App\Http\Controllers\TeacherController::class, 'mySubjects'])->name('subjects');
    Route::get('/subjects/create', [App\Http\Controllers\TeacherController::class, 'createSubject'])->name('subjects.create');
    Route::post('/subjects', [App\Http\Controllers\TeacherController::class, 'storeSubject'])->name('subjects.store');
    Route::get('/subjects/{subject}/edit', [App\Http\Controllers\TeacherController::class, 'editSubject'])->name('subjects.edit');
    Route::put('/subjects/{subject}', [App\Http\Controllers\TeacherController::class, 'updateSubject'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [App\Http\Controllers\TeacherController::class, 'destroySubject'])->name('subjects.destroy');
    Route::get('/subjects/{subjectCode}/students', [App\Http\Controllers\TeacherController::class, 'subjectStudents'])->name('subjects.students');
    Route::get('/subjects/{subjectCode}/seating-chart', [App\Http\Controllers\TeacherController::class, 'seatingChart'])->name('subjects.seating-chart');
    Route::post('/subjects/{subjectCode}/seating-chart', [App\Http\Controllers\TeacherController::class, 'saveSeatingChart'])->name('subjects.seating-chart.save');
    
    // QR Attendance for Teachers
    // Specific routes must come BEFORE the parameterized route
    Route::get('/qr/schedule-info', [App\Http\Controllers\QrAttendanceController::class, 'getScheduleInfo'])->name('qr.schedule');
    Route::post('/qr/start', [App\Http\Controllers\QrAttendanceController::class, 'startTeacherSession'])->name('qr.start');
    Route::post('/qr/refresh', [App\Http\Controllers\QrAttendanceController::class, 'refreshTeacherToken'])->name('qr.refresh');
    Route::post('/qr/stop', [App\Http\Controllers\QrAttendanceController::class, 'stopTeacherSession'])->name('qr.stop');
    Route::get('/qr/clockins', [App\Http\Controllers\QrAttendanceController::class, 'getTeacherClockIns'])->name('qr.clockins');
    // Parameterized route comes last
    Route::get('/qr/{subjectCode}', [App\Http\Controllers\QrAttendanceController::class, 'showTeacherQrPage'])->name('qr');
    
    // Attendance Management
    Route::get('/attendance', [App\Http\Controllers\TeacherController::class, 'attendance'])->name('attendance');
    Route::get('/attendance/preview-pdf', [App\Http\Controllers\TeacherController::class, 'previewAttendancePdf'])->name('attendance.preview');
    Route::get('/attendance/export-pdf', [App\Http\Controllers\TeacherController::class, 'exportAttendancePdf'])->name('attendance.pdf');
    Route::post('/attendance/{attendance}/excuse', [App\Http\Controllers\TeacherController::class, 'excuseAttendance'])->name('attendance.excuse');
    Route::post('/attendance/{attendance}/override', [App\Http\Controllers\TeacherController::class, 'overrideAttendance'])->name('attendance.override');
    Route::post('/corrections/{correction}', [App\Http\Controllers\AttendanceCorrectionController::class, 'update'])->name('teacher.corrections.update');
    
    // Student Management - Full CRUD
    Route::get('/students', [App\Http\Controllers\TeacherController::class, 'students'])->name('students');
    Route::get('/students/preview-pdf', [App\Http\Controllers\TeacherController::class, 'previewStudentsPdf'])->name('students.preview');
    Route::post('/student-notes', [App\Http\Controllers\TeacherController::class, 'storeStudentNote'])->name('notes.store');
    Route::delete('/student-notes/{note}', [App\Http\Controllers\TeacherController::class, 'destroyStudentNote'])->name('notes.destroy');
    Route::get('/students/export-pdf', [App\Http\Controllers\TeacherController::class, 'exportStudentsPdf'])->name('students.pdf');
    Route::get('/student/{student}', [App\Http\Controllers\TeacherController::class, 'studentDetail'])->name('student');
    
    // Absent Report
    Route::get('/absent', [App\Http\Controllers\TeacherController::class, 'absentReport'])->name('absent');
    
    // Notifications Management
    Route::get('/notifications', [App\Http\Controllers\TeacherController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{notification}/archive', [App\Http\Controllers\TeacherController::class, 'archiveNotification'])->name('notifications.archive');
    Route::post('/notifications/{notification}/unarchive', [App\Http\Controllers\TeacherController::class, 'unarchiveNotification'])->name('notifications.unarchive');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\TeacherController::class, 'deleteNotification'])->name('notifications.delete');
    
    // Warning System
    Route::post('/student/{student}/warn', [App\Http\Controllers\TeacherController::class, 'sendWarning'])->name('student.warn');
    Route::get('/student/{student}/absences', [App\Http\Controllers\TeacherController::class, 'absenceSummary'])->name('student.absences');
    
    // Holiday Calendar Management
    Route::get('/calendar', [App\Http\Controllers\TeacherController::class, 'calendar'])->name('calendar');
    Route::post('/holidays', [App\Http\Controllers\TeacherController::class, 'storeHoliday'])->name('holidays.store');
    Route::put('/holidays/{holiday}', [App\Http\Controllers\TeacherController::class, 'updateHoliday'])->name('holidays.update');
    Route::delete('/holidays/{holiday}', [App\Http\Controllers\TeacherController::class, 'destroyHoliday'])->name('holidays.destroy');
    Route::get('/calendar/data', [App\Http\Controllers\TeacherController::class, 'getCalendarData'])->name('calendar.data');
    
    // Reports
    Route::get('/reports', [App\Http\Controllers\TeacherController::class, 'reports'])->name('reports');
    Route::get('/reports/export-pdf', [App\Http\Controllers\TeacherController::class, 'exportPdf'])->name('reports.pdf');
    
    // Profile
    Route::get('/profile', [App\Http\Controllers\TeacherController::class, 'profile'])->name('profile');
    Route::post('/profile', [App\Http\Controllers\TeacherController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/image', [App\Http\Controllers\TeacherController::class, 'updateImage'])->name('profile.image');
    
    // OTP Password Change for Teachers
    Route::post('/otp/send-change', [App\Http\Controllers\OtpController::class, 'sendTeacherChangeOtp'])->middleware('throttle:3,1')->name('otp.change.send');
    Route::post('/otp/change-password', [App\Http\Controllers\OtpController::class, 'changeTeacherPassword'])->name('otp.change');
    
    // Excuse Reviews
    Route::get('/excuse-reviews', [App\Http\Controllers\TeacherController::class, 'excuseReviews'])->name('excuse.reviews');
    Route::post('/excuse/{excuseSubmission}/approve', [App\Http\Controllers\TeacherController::class, 'approveExcuse'])->name('excuse.approve');
    Route::post('/excuse/{excuseSubmission}/reject', [App\Http\Controllers\TeacherController::class, 'rejectExcuse'])->name('excuse.reject');
    Route::get('/excuse/{excuseSubmission}/detail', [App\Http\Controllers\TeacherController::class, 'viewExcuseDetail'])->name('excuse.detail');
    Route::post('/excuse/{excuseSubmission}/comment', [App\Http\Controllers\TeacherController::class, 'storeExcuseComment'])->name('excuse.comment');

    // Messages
    Route::resource('messages', MessageController::class)->only(['index', 'create', 'store', 'show']);
});

// Parent Routes (Parents/Guardians only)
Route::middleware(['auth', 'parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\ParentController::class, 'dashboard'])->name('dashboard');
    
    // Parent-Child Linking
    Route::get('/link-child', [App\Http\Controllers\ParentController::class, 'linkChildForm'])->name('link.form');
    Route::post('/link-child/send-otp', [App\Http\Controllers\ParentController::class, 'sendLinkOtp'])->middleware('throttle:3,1')->name('link.send-otp');
    Route::post('/link-child/verify-otp', [App\Http\Controllers\ParentController::class, 'verifyLinkOtp'])->name('link.verify-otp');
    
    Route::get('/child/{child}', [App\Http\Controllers\ParentController::class, 'childDetail'])->name('child');
    Route::get('/child/{child}/report', [App\Http\Controllers\ParentController::class, 'downloadReport'])->name('child.report');
    Route::get('/child/{child}/warnings', [App\Http\Controllers\ParentController::class, 'childWarnings'])->name('child.warnings');
    Route::get('/child/{child}/excuse/{attendance}', [App\Http\Controllers\ParentController::class, 'submitExcuse'])->name('child.excuse');
    Route::post('/excuse', [App\Http\Controllers\ParentController::class, 'storeExcuse'])->name('excuse.store');
    Route::get('/excuses', [App\Http\Controllers\ParentController::class, 'excuses'])->name('excuses');
    Route::get('/excuse/{excuseSubmission}/detail', [App\Http\Controllers\ParentController::class, 'showExcuse'])->name('excuse.show');
    Route::post('/excuse/{excuseSubmission}/comment', [App\Http\Controllers\ParentController::class, 'storeExcuseComment'])->name('excuse.comment');
    Route::get('/notifications', [App\Http\Controllers\ParentController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [App\Http\Controllers\ParentController::class, 'markNotificationsRead'])->name('notifications.read');
    Route::get('/calendar', [App\Http\Controllers\ParentController::class, 'calendar'])->name('calendar');
    Route::get('/calendar/data', [App\Http\Controllers\ParentController::class, 'getCalendarData'])->name('calendar.data');

    // Messages
    Route::resource('messages', MessageController::class)->only(['index', 'create', 'store', 'show']);
});

// Admin Routes (Admins only)
Route::middleware(['auth', 'admin', 'admin.ip'])->prefix('admin')->name('admin.')->group(function () {
    // 2FA Routes
    Route::get('/2fa', [App\Http\Controllers\AdminController::class, 'twoFactorForm'])->name('2fa.form');
    Route::post('/2fa', [App\Http\Controllers\AdminController::class, 'verifyTwoFactor'])->name('2fa.verify');
    Route::post('/2fa/resend', [App\Http\Controllers\AdminController::class, 'resendTwoFactor'])->name('2fa.resend');


        Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [App\Http\Controllers\AdminController::class, 'dashboardStats'])->name('dashboard.stats');

    // Student management
    Route::get('/students', [App\Http\Controllers\AdminController::class, 'students'])->name('students');
    Route::get('/students/preview-pdf', [App\Http\Controllers\AdminController::class, 'previewStudentsPdf'])->name('students.preview');
    Route::get('/students/export-pdf', [App\Http\Controllers\AdminController::class, 'exportStudentsPdf'])->name('students.pdf');
    Route::get('/student/create', [App\Http\Controllers\AdminController::class, 'createStudent'])->name('student.create');
    Route::post('/student', [App\Http\Controllers\AdminController::class, 'storeStudent'])->name('student.store');
    Route::post('/students/import', [App\Http\Controllers\AdminController::class, 'importStudents'])->name('students.import');
    Route::post('/otp/send-register', [App\Http\Controllers\OtpController::class, 'sendRegisterOtp'])->middleware('throttle:3,1')->name('otp.register.send');
    Route::post('/otp/verify-register', [App\Http\Controllers\OtpController::class, 'verifyRegisterOtp'])->name('otp.register.verify');
    Route::get('/student/{student}', [App\Http\Controllers\AdminController::class, 'studentDetail'])->name('student');
    Route::post('/student/{student}/warn', [App\Http\Controllers\AdminController::class, 'sendWarning'])->name('student.warn');
    Route::get('/student/{student}/edit', [App\Http\Controllers\AdminController::class, 'editStudent'])->name('student.edit');
    Route::put('/student/{student}', [App\Http\Controllers\AdminController::class, 'updateStudent'])->name('student.update');
    Route::delete('/student/{student}', [App\Http\Controllers\AdminController::class, 'destroyStudent'])->name('student.destroy');

    // Subject management
    Route::get('/subjects', [App\Http\Controllers\AdminController::class, 'subjects'])->name('subjects');
    Route::get('/subjects/create', [App\Http\Controllers\AdminController::class, 'createSubject'])->name('subjects.create');
    Route::post('/subjects', [App\Http\Controllers\AdminController::class, 'storeSubject'])->name('subjects.store');

    // QR Management
    Route::get('/qr-management', [App\Http\Controllers\Admin\QrManagementController::class, 'index'])->name('qr');

    Route::get('/subjects/preview-pdf', [App\Http\Controllers\AdminController::class, 'previewSubjectsPdf'])->name('subjects.preview');
    Route::get('/subjects/export-pdf', [App\Http\Controllers\AdminController::class, 'exportSubjectsPdf'])->name('subjects.pdf');
    Route::get('/subjects/{subject}/edit', [App\Http\Controllers\AdminController::class, 'editSubject'])->name('subjects.edit');
    Route::put('/subjects/{subject}', [App\Http\Controllers\AdminController::class, 'updateSubject'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [App\Http\Controllers\AdminController::class, 'destroySubject'])->name('subjects.destroy');

    // Attendance monitoring (read-only)
    Route::get('/attendance', [App\Http\Controllers\AdminController::class, 'attendanceLogs'])->name('attendance');
    Route::get('/attendance/preview-pdf', [App\Http\Controllers\AdminController::class, 'previewAttendancePdf'])->name('attendance.preview');
    Route::get('/attendance/export-pdf', [App\Http\Controllers\AdminController::class, 'exportAttendancePdf'])->name('attendance.pdf');

    // Holiday Calendar Management (system-wide)
    Route::get('/calendar', [App\Http\Controllers\AdminController::class, 'calendar'])->name('calendar');
    Route::post('/holidays', [App\Http\Controllers\AdminController::class, 'storeHoliday'])->name('holidays.store');
    Route::put('/holidays/{holiday}', [App\Http\Controllers\AdminController::class, 'updateHoliday'])->name('holidays.update');
    Route::delete('/holidays/{holiday}', [App\Http\Controllers\AdminController::class, 'destroyHoliday'])->name('holidays.destroy');
    Route::get('/calendar/data', [App\Http\Controllers\AdminController::class, 'getCalendarData'])->name('calendar.data');

    // Notifications (system alerts)
    Route::get('/notifications', [App\Http\Controllers\AdminController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\AdminController::class, 'markAllNotificationsRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{notification}/archive', [App\Http\Controllers\AdminController::class, 'archiveNotification'])->name('notifications.archive');
    Route::post('/notifications/{notification}/unarchive', [App\Http\Controllers\AdminController::class, 'unarchiveNotification'])->name('notifications.unarchive');
    Route::delete('/notifications/{notification}', [App\Http\Controllers\AdminController::class, 'deleteNotification'])->name('notifications.delete');

    // Profile
    Route::get('/profile', [App\Http\Controllers\AdminController::class, 'profile'])->name('profile');
    Route::post('/profile/image', [App\Http\Controllers\AdminController::class, 'updateImage'])->name('profile.image');
    
    // Settings (Super Admin Only)
    Route::middleware('admin.super')->group(function () {
        Route::get('/settings', [App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
        Route::post('/settings/update', [App\Http\Controllers\AdminController::class, 'updateSettings'])->name('settings.update');
    });
    
    Route::post('/password', [App\Http\Controllers\AdminController::class, 'updatePassword'])->name('password.update');

    // Activity Log
    Route::get('/activity-log', [App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity.log');

    // Teacher Management
    Route::get('/teachers', [App\Http\Controllers\AdminController::class, 'teachers'])->name('teachers');
    Route::get('/teachers/export-excel', [App\Http\Controllers\AdminController::class, 'exportTeachersExcel'])->name('teachers.export');
    Route::get('/teachers/export-pdf', [App\Http\Controllers\AdminController::class, 'exportTeachersPdf'])->name('teachers.pdf');
    Route::get('/teacher/create', [App\Http\Controllers\AdminController::class, 'createTeacher'])->name('teacher.create');
    Route::post('/teacher', [App\Http\Controllers\AdminController::class, 'storeTeacher'])->name('teacher.store');
    Route::get('/teacher/{teacher}/edit', [App\Http\Controllers\AdminController::class, 'editTeacher'])->name('teacher.edit');
    Route::put('/teacher/{teacher}', [App\Http\Controllers\AdminController::class, 'updateTeacher'])->name('teacher.update');
    Route::delete('/teacher/{teacher}', [App\Http\Controllers\AdminController::class, 'destroyTeacher'])->name('teacher.destroy');

    // Bulk Excuse Approval
    Route::post('/excuses/bulk-approve', [App\Http\Controllers\AdminController::class, 'bulkApproveExcuses'])->name('excuses.bulk.approve');
    Route::post('/excuses/bulk-reject', [App\Http\Controllers\AdminController::class, 'bulkRejectExcuses'])->name('excuses.bulk.reject');
    Route::get('/excuses', [App\Http\Controllers\AdminController::class, 'excuses'])->name('excuses');
    Route::post('/excuse/{excuseSubmission}/approve', [App\Http\Controllers\AdminController::class, 'approveExcuse'])->name('excuse.approve');
    Route::post('/excuse/{excuseSubmission}/reject', [App\Http\Controllers\AdminController::class, 'rejectExcuse'])->name('excuse.reject');

    // Seating Chart
    Route::get('/seating-chart/{subjectCode}', [App\Http\Controllers\AdminController::class, 'seatingChart'])->name('seating.chart');
    Route::post('/seating-chart/{subjectCode}', [App\Http\Controllers\AdminController::class, 'saveSeatingChart'])->name('seating.chart.save');

    // Excel Export
    Route::get('/attendance/export-excel', [App\Http\Controllers\AdminController::class, 'exportAttendance'])->name('attendance.export');
    
    // New Academic Modules (SaaS Design Expansion)
    Route::resource('departments', App\Http\Controllers\Admin\DepartmentController::class);
    Route::resource('courses', App\Http\Controllers\Admin\CourseController::class);
    Route::resource('sections', App\Http\Controllers\Admin\SectionController::class);
    Route::resource('class-schedules', App\Http\Controllers\Admin\ClassScheduleController::class);
    
    // System & Communication Modules
    Route::resource('announcements', App\Http\Controllers\Admin\AnnouncementController::class);
    // Super Admin Routes (Health, Backups, RBAC)
    Route::middleware('admin.super')->group(function () {
        Route::get('/system-health', [App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('system-health.index');
        Route::get('/backups', [App\Http\Controllers\Admin\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups/create', [App\Http\Controllers\Admin\BackupController::class, 'create'])->name('backups.create');
        Route::delete('/backups/{backup}', [App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('backups.destroy');
        
        Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
    });
    
    // Omni-Search
    Route::get('/search', [App\Http\Controllers\Admin\SearchController::class, 'index'])->name('search');

});