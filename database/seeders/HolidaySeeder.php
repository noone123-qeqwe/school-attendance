<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to assign as creator
        $admin = \App\Models\User::where('role', 'admin')->first();
        
        if (!$admin) {
            $this->command->error('No admin user found. Please create an admin user first.');
            return;
        }

        $holidays = [
            // 2026 Philippine National Holidays
            [
                'date' => '2026-01-01',
                'name' => 'New Year\'s Day',
                'description' => 'First day of the year',
                'type' => 'national'
            ],
            [
                'date' => '2026-02-25',
                'name' => 'People Power Anniversary',
                'description' => 'EDSA Revolution commemoration',
                'type' => 'national'
            ],
            [
                'date' => '2026-04-09',
                'name' => 'Araw ng Kagitingan',
                'description' => 'Day of Valor - Bataan and Corregidor',
                'type' => 'national'
            ],
            [
                'date' => '2026-05-01',
                'name' => 'Labor Day',
                'description' => 'International Workers\' Day',
                'type' => 'national'
            ],
            [
                'date' => '2026-05-27',
                'name' => 'Eid\'l Adha',
                'description' => 'Feast of Sacrifice - Islamic holiday',
                'type' => 'national'
            ],
            [
                'date' => '2026-06-12',
                'name' => 'Independence Day',
                'description' => 'Philippine Independence from Spain',
                'type' => 'national'
            ],
            [
                'date' => '2026-08-31',
                'name' => 'National Heroes Day',
                'description' => 'Honoring Filipino heroes',
                'type' => 'national'
            ],
            [
                'date' => '2026-11-30',
                'name' => 'Bonifacio Day',
                'description' => 'Birth anniversary of Andres Bonifacio',
                'type' => 'national'
            ],
            [
                'date' => '2026-12-25',
                'name' => 'Christmas Day',
                'description' => 'Birth of Jesus Christ',
                'type' => 'national'
            ],
            [
                'date' => '2026-12-30',
                'name' => 'Rizal Day',
                'description' => 'Death anniversary of Dr. Jose Rizal',
                'type' => 'national'
            ],
            // Common school holidays
            [
                'date' => '2026-12-24',
                'name' => 'Christmas Eve',
                'description' => 'Day before Christmas',
                'type' => 'school'
            ],
            [
                'date' => '2026-12-31',
                'name' => 'New Year\'s Eve',
                'description' => 'Last day of the year',
                'type' => 'school'
            ]
        ];

        foreach ($holidays as $holiday) {
            \App\Models\Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                [
                    'name' => $holiday['name'],
                    'description' => $holiday['description'],
                    'type' => $holiday['type'],
                    'created_by' => $admin->id
                ]
            );
        }

        $this->command->info('Default holidays seeded successfully!');
    }
}
