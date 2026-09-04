<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ══════════════════════════════════════════════════════════════
        // 👪 OFFICIAL PARENT ACCOUNTS (Linked to Capstone Students)
        // ══════════════════════════════════════════════════════════════
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
        ];

        $createdCount = 0;
        $linkedCount = 0;
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
                $attributes['password'] = Hash::make('parent123');
                $parent = User::create($attributes);
                $createdCount++;
            }

            // Link parent to the designated students
            foreach ($pData['student_numbers'] as $studentNumber) {
                $student = User::where('student_number', $studentNumber)
                    ->where('role', 'student')
                    ->first();

                if ($student) {
                    // Update student's guardian_email
                    $student->update(['guardian_email' => $parent->email]);

                    // Insert or ignore into parent_student pivot table
                    DB::table('parent_student')->insertOrIgnore([
                        'parent_id'  => $parent->id,
                        'student_id' => $student->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $linkedCount++;
                }
            }
        }

        $this->command->info("ParentSeeder finished: " . count($parentsData) . " parents processed ({$createdCount} new), {$linkedCount} parent-student links established.");
    }
}
