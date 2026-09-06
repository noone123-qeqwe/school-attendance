<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'course', 'year_level'])
            ->logOnlyDirty();
    }

    /**
     * Get and set the user's name correctly capitalized.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) return null;
                $titleCased = ucwords(strtolower($value));
                // Add a period to any single uppercase letter that acts as an initial (bounded by spaces)
                return preg_replace('/\b([A-Z])\b(?!\.)/u', '$1.', $titleCased);
            },
            set: function (?string $value) {
                if (!$value) return null;
                $titleCased = ucwords(strtolower($value));
                return preg_replace('/\b([A-Z])\b(?!\.)/u', '$1.', $titleCased);
            },
        );
    }

    /**
     * The attributes that are mass assignable.
     * Including the fields required for the Smart Classroom Attendance System.
     */
 protected $fillable = [
    'name',
    'student_number',
    'employee_id',
    'course',
    'department',
    'position',
    'specialization',
    'year_level',
    'semester',
    'section',
    'guardian_email',
    'email',
    'email_verified_at',
    'password',
    'must_change_password',
    'role',
    'admin_sub_role',
    'profile_image',
    'phone',
    'notification_preferences',
    'rfid_tag',
    'kiosk_pin',
    'is_active',
];

    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true) && !$this->trashed();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isDepartmentHead(): bool
    {
        return $this->role === 'department_head';
    }

    public function children()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id');
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'kiosk_pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'notification_preferences' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * RELATIONSHIP: Attendance
     * This allows us to call $user->attendances in the dashboard.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * RELATIONSHIP: Subjects (for teachers)
     * Get subjects taught by this teacher
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'instructor_id');
    }

    /**
     * RELATIONSHIP: Subjects (alternative method using instructor_id)
     */
    public function teachingSubjects()
    {
        return $this->hasMany(Subject::class, 'instructor_id');
    }

    /**
     * RELATIONSHIP: Enrolled Subjects (for students)
     */
    public function enrolledSubjects()
    {
        return $this->belongsToMany(Subject::class, 'enrollments', 'user_id', 'subject_id')
                    ->withTimestamps();
    }

    /**
     * Get all subjects for this student (explicitly enrolled + implicitly via year level / semester)
     */
    public function getAllSubjects()
    {
        if (!$this->isStudent()) return collect();
        
        $explicit = $this->enrolledSubjects()->get();
        
        $query = Subject::where('year_level', $this->year_level)
            ->where('semester', $this->semester);
            
        // If subject specifies a course, it must match the student's course
        $query->where(function ($q) {
            $q->whereNull('course')
              ->orWhere('course', '')
              ->orWhere('course', $this->course);
        });
        
        // If subject specifies a section, it must match the student's section
        $query->where(function ($q) {
            $q->whereNull('section')
              ->orWhere('section', '')
              ->orWhere('section', $this->section);
        });
            
        $implicit = $query->get();
            
        return $explicit->merge($implicit)->unique('id')->values();
    }

    public function excuseSubmissions()
    {
        return $this->hasMany(ExcuseSubmission::class);
    }

    public function webauthnCredentials()
    {
        return $this->hasMany(WebauthnCredential::class);
    }

    public function deviceBinding()
    {
        return $this->hasOne(DeviceBinding::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'student_id');
    }

    public function organizedEvents()
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function invitedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_attendees', 'user_id', 'event_id')
                    ->withPivot('response', 'decline_reason')
                    ->withTimestamps();
    }

    /**
     * Generate a unique student number automatically.
     * Format: YYYYNNN (e.g., 2026001, 2026002)
     * Where YYYY is current year and NNN is sequential 3-digit number
     */
    public static function generateStudentNumber(): string
    {
        $year = date('Y');
        
        // Find the latest student number for this year
        $latestStudent = static::where('student_number', 'LIKE', $year . '%')
            ->where('role', 'student')
            ->orderByRaw('CAST(student_number AS UNSIGNED) DESC')
            ->first();
        
        if ($latestStudent && $latestStudent->student_number) {
            // Extract the numeric part after the year and increment
            $studentNumberStr = (string) $latestStudent->student_number;
            if (strlen($studentNumberStr) >= 4 && substr($studentNumberStr, 0, 4) === $year) {
                $lastNumber = (int) substr($studentNumberStr, 4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
        } else {
            // Start from 1 if no students exist for this year
            $newNumber = 1;
        }
        
        // Format: YYYY + 3-digit number (e.g., 2026001)
        $studentNumber = $year . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        
        // Ensure uniqueness (in case of race conditions)
        while (static::where('student_number', $studentNumber)->exists()) {
            $newNumber++;
            $studentNumber = $year . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return $studentNumber;
    }
}
