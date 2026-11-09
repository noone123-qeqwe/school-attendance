<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ClassScheduleController;
use App\Models\Course;
use App\Models\Department;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassScheduleLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PostgresScheduleConcurrencyTest extends TestCase
{
    public function test_second_concurrent_admin_request_is_rejected_after_first_commits(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This concurrency test requires PostgreSQL row locks.');
        }
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('This concurrency test requires the pcntl extension.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);

        $firstAdmin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $secondAdmin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $firstTeacher = User::factory()->teacher()->create();
        $secondTeacher = User::factory()->teacher()->create();
        $department = Department::create(['name' => 'Concurrency Department', 'code' => 'CON']);
        $course = Course::create(['department_id' => $department->id, 'name' => 'Concurrency Course', 'code' => 'CONC']);
        $section = Section::create(['course_id' => $course->id, 'name' => 'A', 'year_level' => 1]);
        $firstSubject = $this->subject('CON101', $firstTeacher, $section);
        $secondSubject = $this->subject('CON102', $secondTeacher, $section);

        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'attendance-schedule-concurrency-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700, true);
        $readyPath = $directory . DIRECTORY_SEPARATOR . 'first-ready';
        $firstResultPath = $directory . DIRECTORY_SEPARATOR . 'first.json';
        $secondResultPath = $directory . DIRECTORY_SEPARATOR . 'second.json';

        try {
            $firstPid = pcntl_fork();
            if ($firstPid === 0) {
                $this->runFirstRequest($firstAdmin->id, $this->payload($firstSubject, $firstTeacher, $section), $readyPath, $firstResultPath);
            }
            $this->assertGreaterThan(0, $firstPid);

            $deadline = microtime(true) + 10;
            while (!is_file($readyPath) && microtime(true) < $deadline) {
                usleep(20_000);
            }
            $this->assertFileExists($readyPath, 'The first request did not acquire the schedule lock.');

            $secondPid = pcntl_fork();
            if ($secondPid === 0) {
                $this->runRequest($secondAdmin->id, $this->payload($secondSubject, $secondTeacher, $section), $secondResultPath);
            }
            $this->assertGreaterThan(0, $secondPid);

            pcntl_waitpid($firstPid, $firstStatus);
            pcntl_waitpid($secondPid, $secondStatus);
            $this->assertTrue(pcntl_wifexited($firstStatus));
            $this->assertTrue(pcntl_wifexited($secondStatus));

            $first = json_decode((string) file_get_contents($firstResultPath), true, flags: JSON_THROW_ON_ERROR);
            $second = json_decode((string) file_get_contents($secondResultPath), true, flags: JSON_THROW_ON_ERROR);

            $this->assertSame('created', $first['outcome']);
            $this->assertSame('rejected', $second['outcome']);
            $this->assertArrayHasKey('room', $second['errors']);
            $this->assertGreaterThanOrEqual($first['committed_at'], $second['finished_at']);
            $this->assertDatabaseCount('class_schedules', 1);
            $this->assertDatabaseHas('class_schedules', ['subject_id' => $firstSubject->id]);
            $this->assertDatabaseMissing('class_schedules', ['subject_id' => $secondSubject->id]);
        } finally {
            foreach ([$readyPath, $firstResultPath, $secondResultPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    /** @param array<string, mixed> $payload */
    private function runFirstRequest(int $adminId, array $payload, string $readyPath, string $resultPath): never
    {
        $this->reconnectForChild();

        try {
            Auth::loginUsingId($adminId);
            DB::beginTransaction();
            ClassScheduleLock::acquire();
            file_put_contents($readyPath, 'locked', LOCK_EX);
            app(ClassScheduleController::class)->store(Request::create('/admin/class-schedules', 'POST', $payload));
            usleep(500_000);
            DB::commit();
            $this->writeResult($resultPath, ['outcome' => 'created', 'committed_at' => microtime(true)]);
            exit(0);
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->writeResult($resultPath, ['outcome' => 'failed', 'exception' => $exception::class]);
            exit(1);
        }
    }

    /** @param array<string, mixed> $payload */
    private function runRequest(int $adminId, array $payload, string $resultPath): never
    {
        $this->reconnectForChild();

        try {
            Auth::loginUsingId($adminId);
            app(ClassScheduleController::class)->store(Request::create('/admin/class-schedules', 'POST', $payload));
            $this->writeResult($resultPath, ['outcome' => 'created', 'finished_at' => microtime(true)]);
            exit(0);
        } catch (ValidationException $exception) {
            $this->writeResult($resultPath, [
                'outcome' => 'rejected',
                'errors' => $exception->errors(),
                'finished_at' => microtime(true),
            ]);
            exit(0);
        } catch (\Throwable $exception) {
            $this->writeResult($resultPath, ['outcome' => 'failed', 'exception' => $exception::class]);
            exit(1);
        }
    }

    private function reconnectForChild(): void
    {
        DB::disconnect();
        DB::purge();
        DB::reconnect();
    }

    /** @param array<string, mixed> $result */
    private function writeResult(string $path, array $result): void
    {
        file_put_contents($path, json_encode($result, JSON_THROW_ON_ERROR), LOCK_EX);
    }

    private function subject(string $code, User $teacher, Section $section): Subject
    {
        return Subject::create([
            'code' => $code,
            'name' => $code . ' Class',
            'units' => 3,
            'year_level' => $section->year_level,
            'semester' => 1,
            'course' => $section->course->code,
            'section' => $section->name,
            'instructor_id' => $teacher->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Subject $subject, User $teacher, Section $section): array
    {
        return [
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'day_of_week' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'Concurrency Room',
        ];
    }
}
