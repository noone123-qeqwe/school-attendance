<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\BackupLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_responses()
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');
    }

    public function test_registration_requires_valid_csrf_token()
    {
        $middleware = new class(app(), app('encrypter')) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
            protected function runningUnitTests()
            {
                return false; // Force actual CSRF check in test
            }
        };

        $request = \Illuminate\Http\Request::create('/register', 'POST', ['_token' => 'invalid-token']);
        $session = app('session')->driver();
        $session->put('_token', 'real-token');
        $request->setLaravelSession($session);

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $middleware->handle($request, function () {
            return response('OK');
        });
    }

    public function test_teacher_materials_upload_rejects_executable_php_files()
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'must_change_password' => false,
        ]);

        $subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Intro to CS',
            'instructor_id' => $teacher->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $maliciousFile = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');

        $response = $this->actingAs($teacher)->post(route('teacher.materials.store', $subject->code), [
            'title' => 'Lecture Note',
            'description' => 'Test',
            'file' => $maliciousFile,
        ]);

        $response->assertSessionHasErrors('file');
        Storage::disk('public')->assertMissing('materials/CS101/shell.php');
    }

    public function test_teacher_materials_upload_allows_valid_documents()
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'must_change_password' => false,
        ]);

        $subject = Subject::create([
            'code' => 'CS102',
            'name' => 'Data Structures',
            'instructor_id' => $teacher->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $validPdf = UploadedFile::fake()->create('syllabus.pdf', 500, 'application/pdf');

        $response = $this->actingAs($teacher)->post(route('teacher.materials.store', $subject->code), [
            'title' => 'Syllabus',
            'description' => 'Course syllabus',
            'file' => $validPdf,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subject_materials', [
            'subject_id' => $subject->id,
            'title' => 'Syllabus',
        ]);
    }

    public function test_non_super_admin_cannot_reset_admin_passwords()
    {
        $regularAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'staff_admin',
            'must_change_password' => false,
        ]);

        $targetAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'department_admin',
            'password' => Hash::make('OriginalPass123!'),
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($regularAdmin)->post(route('admin.user.reset_password', $targetAdmin->id), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHas('error');
        $targetAdmin->refresh();
        $this->assertTrue(Hash::check('OriginalPass123!', $targetAdmin->password));
    }

    public function test_super_admin_can_reset_admin_passwords()
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'must_change_password' => false,
        ]);

        $targetAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'staff_admin',
            'password' => Hash::make('OriginalPass123!'),
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.user.reset_password', $targetAdmin->id), [
            'password' => 'NewSecretPass123!',
            'password_confirmation' => 'NewSecretPass123!',
        ]);

        $response->assertSessionHas('success');
        $targetAdmin->refresh();
        $this->assertTrue(Hash::check('NewSecretPass123!', $targetAdmin->password));
    }

    public function test_recovery_login_unifies_error_message_to_prevent_user_enumeration()
    {
        $response = $this->post('/recovery/login', [
            'identifier' => 'nonexistent@example.com',
            'recovery_code' => 'XXXX-XXXX',
        ]);

        $response->assertSessionHasErrors(['identifier' => 'Invalid credentials or recovery code.']);
    }

    public function test_backup_download_is_forbidden_for_non_super_admin()
    {
        $regularAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'staff_admin',
            'must_change_password' => false,
        ]);

        $backup = BackupLog::create([
            'filename' => 'backup_2026_01_01.sql',
            'path' => 'backups/backup_2026_01_01.sql',
            'size' => 1024,
        ]);

        $response = $this->actingAs($regularAdmin)->get(route('admin.backups.download', $backup->id));
        $response->assertStatus(403);
    }

    public function test_csv_export_sanitizes_dangerous_formula_characters()
    {
        $maliciousStudent = User::factory()->make([
            'name' => '=cmd|\' /C calc\'!A0',
            'student_number' => '+63912345678',
            'email' => '@malicious@example.com',
            'attendance_rate' => 100,
        ]);

        $export = new \App\Exports\StudentsExport(collect([$maliciousStudent]));
        $mapped = $export->collection()->first();

        // Ensure leading dangerous formula characters are prefixed with single quote
        $this->assertStringStartsWith("'", $mapped['name']);
        $this->assertStringStartsWith("'", $mapped['student_id']);
        $this->assertStringStartsWith("'", $mapped['email']);
    }

    public function test_csv_student_import_does_not_demote_or_overwrite_admin_accounts()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'head_admin@school.edu',
            'name' => 'Head Admin',
            'must_change_password' => false,
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'must_change_password' => false,
        ]);

        $csvContent = "Name,StudentNumber,Email,Course,YearLevel,Semester,Section\n";
        $csvContent .= "Fake Student,9999999,head_admin@school.edu,BSCS,1,1,A\n";

        $csvFile = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($superAdmin)->post(route('admin.students.import'), [
            'csv_file' => $csvFile,
        ]);

        $admin->refresh();
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('Head Admin', $admin->name);
    }
}
