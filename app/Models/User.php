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
     * Get the resolved URL for the user's profile picture or default avatar.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_image) {
            if (str_starts_with($this->profile_image, 'http://') || str_starts_with($this->profile_image, 'https://')) {
                return $this->profile_image;
            }
            if (str_starts_with($this->profile_image, '/')) {
                return $this->profile_image;
            }
            return asset('storage/' . $this->profile_image);
        }

        $initials = urlencode($this->name ?: 'User');
        $bg = $this->isTeacher() ? '7c2d12' : ($this->isAdmin() ? '800000' : '800000');
        return "https://ui-avatars.com/api/?name={$initials}&background={$bg}&color=fff&size=256";
    }

    /**
     * Get profile photo URL with timestamp version parameter for cache-busting.
     */
    public function getProfilePhotoUrlWithVersionAttribute(): string
    {
        $url = $this->profile_photo_url;
        $version = $this->updated_at ? $this->updated_at->timestamp : time();
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'v=' . $version;
    }

    /**
     * Determine if the user has an uploaded custom profile picture.
     */
    public function getHasCustomProfileImageAttribute(): bool
    {
        return !empty($this->profile_image);
    }

    /**
     * Additional attributes to append to array/JSON representation.
     */
    protected $appends = [
        'profile_photo_url',
        'has_custom_profile_image',
    ];

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

    /**
     * Find a user by any valid identifier:
     * - Student number (exact, case-insensitive, or padded with leading zero for institutional 7-digit IDs)
     * - Email (exact or case-insensitive)
     * - Employee ID (exact, case-insensitive, or stripped)
     * - Database primary key ID (if numeric)
     * - Normalized alphanumeric formats (stripping hyphens and spaces)
     */
    public static function findByIdentifier(?string $identifier, bool $withTrashed = true): ?self
    {
        $raw = trim((string) $identifier);
        if ($raw === '') {
            return null;
        }

        $findInQuery = function ($baseQuery) use ($raw): ?self {
            $lower = strtolower($raw);

            // 1. Direct match on standard fields (exact, trimmed, or lowercase)
            $user = (clone $baseQuery)->where(function ($q) use ($raw, $lower) {
                $q->where('student_number', $raw)
                  ->orWhere('email', $raw)
                  ->orWhere('employee_id', $raw)
                  ->orWhereRaw('LOWER(email) = ?', [$lower])
                  ->orWhereRaw('LOWER(student_number) = ?', [$lower])
                  ->orWhereRaw('LOWER(employee_id) = ?', [$lower])
                  ->orWhereRaw('TRIM(email) = ?', [$raw])
                  ->orWhereRaw('TRIM(student_number) = ?', [$raw])
                  ->orWhereRaw('TRIM(employee_id) = ?', [$raw])
                  ->orWhereRaw('LOWER(TRIM(email)) = ?', [$lower])
                  ->orWhereRaw('LOWER(TRIM(student_number)) = ?', [$lower])
                  ->orWhereRaw('LOWER(TRIM(employee_id)) = ?', [$lower]);
            })->first();

            if ($user) {
                return $user;
            }

            // 2. If numeric, check institutional 7-digit zero-padded or unpadded student number / employee id
            if (ctype_digit($raw) || is_numeric($raw)) {
                $num = (int) $raw;
                $unpadded = (string) $num;
                $padded7 = sprintf('%07d', $num);

                // Check unpadded (e.g. user typed 0703250 and DB has 703250) or padded (user typed 703250 and DB has 0703250)
                $user = (clone $baseQuery)->where(function ($q) use ($unpadded, $padded7) {
                    $q->where('student_number', $padded7)
                      ->orWhere('employee_id', $padded7)
                      ->orWhere('student_number', $unpadded)
                      ->orWhere('employee_id', $unpadded);
                })->first();

                if ($user) {
                    return $user;
                }

                // Fallback to primary key ID only after student_number / employee_id checks
                $user = (clone $baseQuery)->where('id', $num)->first();
                if ($user) {
                    return $user;
                }
            }

            // 3. Clean non-alphanumeric characters (e.g. hyphens, spaces in 070-3250, T-2024-001)
            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $raw);
            if ($clean !== '' && $clean !== $raw) {
                $cleanLower = strtolower($clean);
                $user = (clone $baseQuery)->where(function ($q) use ($clean, $cleanLower) {
                    $q->where('student_number', $clean)
                      ->orWhere('employee_id', $clean)
                      ->orWhereRaw('LOWER(student_number) = ?', [$cleanLower])
                      ->orWhereRaw('LOWER(employee_id) = ?', [$cleanLower])
                      ->orWhereRaw("REPLACE(REPLACE(student_number, '-', ''), ' ', '') = ?", [$clean])
                      ->orWhereRaw("REPLACE(REPLACE(employee_id, '-', ''), ' ', '') = ?", [$clean]);
                })->first();

                if ($user) {
                    return $user;
                }

                if (ctype_digit($clean)) {
                    $num = (int) $clean;
                    $unpadded = (string) $num;
                    $padded7 = sprintf('%07d', $num);
                    $user = (clone $baseQuery)->where(function ($q) use ($unpadded, $padded7) {
                        $q->where('student_number', $padded7)
                          ->orWhere('employee_id', $padded7)
                          ->orWhere('student_number', $unpadded)
                          ->orWhere('employee_id', $unpadded);
                    })->first();

                    if ($user) {
                        return $user;
                    }
                }
            }

            return null;
        };

        // Always check active (non-deleted) users first
        $activeUser = $findInQuery(static::query()->whereNull('deleted_at'));
        if ($activeUser) {
            return $activeUser;
        }

        // Only search soft-deleted users as a fallback if specifically allowed
        if ($withTrashed) {
            return $findInQuery(static::withTrashed());
        }

        return null;
    }

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

    /**
     * Check if the user is a super admin.
     * Null admin_sub_role is treated as super_admin for backward compatibility.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin' && ($this->admin_sub_role === 'super_admin' || $this->admin_sub_role === null);
    }

    public function isDepartmentHead(): bool
    {
        return $this->role === 'department_head';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
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
