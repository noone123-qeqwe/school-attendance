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
        // 👪 PARENT ACCOUNTS CONFIGURATION
        // ══════════════════════════════════════════════════════════════
        $parentsData = [
            [
                'name' => 'Eduardo & Teresa Santos',
                'email' => 'parent@example.com', // Demo parent account
                'phone' => '09171234567',
                'student_numbers' => ['2310843', '2024004'], // Mark Angelo Santos & Ana Marie Reyes (Multi-child test)
            ],
            [
                'name' => 'Roberto Garcia',
                'email' => 'garcia.parent@example.com',
                'phone' => '09182345678',
                'student_numbers' => ['2024002'], // Maria Isabel Garcia
            ],
            [
                'name' => 'Elena Cruz',
                'email' => 'cruz.parent@example.com',
                'phone' => '09193456789',
                'student_numbers' => ['2024003'], // Carlos David Cruz
            ],
            [
                'name' => 'Fernando Dela Cruz',
                'email' => 'delacruz.parent@example.com',
                'phone' => '09204567890',
                'student_numbers' => ['2024005'], // Robert James Dela Cruz
            ],
            [
                'name' => 'Nasser Amatos',
                'email' => 'amatos.parent@example.com',
                'phone' => '09215678901',
                'student_numbers' => ['0703250'], // Ibn Kervi J. Amatos
            ],
            [
                'name' => 'Rowena Portugal',
                'email' => 'portugal.parent@example.com',
                'phone' => '09226789012',
                'student_numbers' => ['2312215'], // Karla Villamor Portugal
            ],
            [
                'name' => 'Maricel Herminado',
                'email' => 'herminado.parent@example.com',
                'phone' => '09237890123',
                'student_numbers' => ['2311969'], // Janessa Almosara Herminado
            ],
            [
                'name' => 'Clarito Ole',
                'email' => 'ole.parent@example.com',
                'phone' => '09248901234',
                'student_numbers' => ['1234567'], // Jack C. Ole
            ],
            [
                'name' => 'Merly Espinas',
                'email' => 'espinas.parent@example.com',
                'phone' => '09259012345',
                'student_numbers' => ['17J0053'], // Jolina Mae Astibe Espinas
            ],
        ];

        $createdCount = 0;
        $linkedCount = 0;
        $now = now();

        foreach ($parentsData as $pData) {
            $parent = User::where('email', strtolower($pData['email']))->first();

            $attributes = [
                'name'                     => $pData['name'],
                'email'                    => strtolower($pData['email']),
                'role'                     => 'parent',
                'phone'                    => $pData['phone'],
                'email_verified_at'        => $now,
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
                $attributes['password'] = Hash::make('password');
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
