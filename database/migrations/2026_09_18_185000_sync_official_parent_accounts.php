<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensures all official parent accounts and parent-student links are synchronized
     * across all environments (local, staging, production).
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $parentsData = [
            [
                'name'            => 'Clarito Ole',
                'email'           => 'clarito.ole@gmail.com',
                'phone'           => '09248901234',
                'student_numbers' => ['1234567'], // Jack C. Ole
            ],
            [
                'name'            => 'Nasser Amatos',
                'email'           => 'nasser.amatos@gmail.com',
                'phone'           => '09215678901',
                'student_numbers' => ['0703250'], // Ibn Kervi J. Amatos
            ],
            [
                'name'            => 'Rowena Portugal',
                'email'           => 'rowena.portugal@gmail.com',
                'phone'           => '09226789012',
                'student_numbers' => ['2312215'], // Karla Villamor Portugal
            ],
            [
                'name'            => 'Maricel Herminado',
                'email'           => 'maricel.herminado@gmail.com',
                'phone'           => '09237890123',
                'student_numbers' => ['2311969'], // Janessa Almosara Herminado
            ],
            [
                'name'            => 'Merly Espinas',
                'email'           => 'merly.espinas@gmail.com',
                'phone'           => '09259012345',
                'student_numbers' => ['17J0053'], // Jolina Mae Astibe Espinas
            ],
            [
                'name'            => 'John Doe (parent)',
                'email'           => 'parent@example.com',
                'phone'           => '09170000001',
                'student_numbers' => ['0703250'], // Ibn Kervi J. Amatos
            ],
        ];

        $now = now();

        foreach ($parentsData as $pData) {
            $parent = User::withTrashed()->where('email', strtolower($pData['email']))->first();

            $attributes = [
                'name'                     => $pData['name'],
                'email'                    => strtolower($pData['email']),
                'role'                     => 'parent',
                'phone'                    => $pData['phone'],
                'email_verified_at'        => $now,
                'is_active'                => true,
                'password'                 => Hash::make('parent123'),
                'notification_preferences' => [
                    'email_notifications' => true,
                    'push_notifications'  => true,
                    'sms_notifications'   => true,
                    'email'               => true,
                    'in_app'              => true,
                ],
            ];

            if ($parent) {
                if ($parent->trashed()) {
                    $parent->restore();
                }
                $parent->update($attributes);
            } else {
                $parent = User::create($attributes);
            }

            foreach ($pData['student_numbers'] as $studentNumber) {
                $student = User::where('student_number', $studentNumber)
                    ->where('role', 'student')
                    ->first();

                if ($student) {
                    $student->update(['guardian_email' => $parent->email]);

                    DB::table('parent_student')->insertOrIgnore([
                        'parent_id'  => $parent->id,
                        'student_id' => $student->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive
    }
};
