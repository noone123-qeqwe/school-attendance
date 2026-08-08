<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'course', 'year_level'])
            ->logOnlyDirty();
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
    'password',
    'role',
    'profile_image',
    'phone',
    'notification_preferences',
    'rfid_tag',
    'kiosk_pin',
];

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
            'password' => 'hashed',
            'notification_preferences' => 'array',
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
        
        $implicit = Subject::where('year_level', $this->year_level)
            ->where('semester', $this->semester)
            ->get();
            
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
}
