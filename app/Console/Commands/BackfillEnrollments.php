<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class BackfillEnrollments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollments:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfills the enrollments table based on student and subject fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting enrollment backfill...');
        
        $subjects = Subject::all();
        $totalEnrolled = 0;
        
        foreach ($subjects as $subject) {
            $studentsQuery = User::where('role', 'student')
                ->where('year_level', $subject->year_level)
                ->where('semester', $subject->semester);
            
            if (!empty($subject->course)) {
                $studentsQuery->where('course', $subject->course);
            }
            if (!empty($subject->section)) {
                $studentsQuery->where('section', $subject->section);
            }
            
            $students = $studentsQuery->get();
            
            $enrolledCount = 0;
            foreach ($students as $student) {
                // Ignore if already enrolled
                $exists = DB::table('enrollments')
                    ->where('user_id', $student->id)
                    ->where('subject_id', $subject->id)
                    ->exists();
                    
                if (!$exists) {
                    DB::table('enrollments')->insert([
                        'user_id' => $student->id,
                        'subject_id' => $subject->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $enrolledCount++;
                    $totalEnrolled++;
                }
            }
            
            $this->info("Enrolled {$enrolledCount} students in {$subject->code}");
        }
        
        $this->info("Successfully backfilled {$totalEnrolled} total enrollments.");
    }
}
