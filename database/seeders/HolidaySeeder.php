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

        // Pre-defined dates for holidays that don't follow simple Gregorian rules
        $movableDates = [
            2024 => ['cny' => '02-10', 'fitr' => '04-10', 'adha' => '06-17'],
            2025 => ['cny' => '01-29', 'fitr' => '03-31', 'adha' => '06-06'],
            2026 => ['cny' => '02-17', 'fitr' => '03-20', 'adha' => '05-27'],
            2027 => ['cny' => '02-06', 'fitr' => '03-10', 'adha' => '05-16'],
            2028 => ['cny' => '01-26', 'fitr' => '02-28', 'adha' => '05-05'],
        ];

        for ($year = 2026; $year <= 2050; $year++) {
            // Calculate Holy Week dates using easter_days (avoids 2037 limit of easter_date)
            $easterDays = easter_days($year);
            $easter = new \DateTime("$year-03-21");
            $easter->modify("+$easterDays days");
            
            $maundyThursday = (clone $easter)->modify('-3 days')->format('Y-m-d');
            $goodFriday = (clone $easter)->modify('-2 days')->format('Y-m-d');
            $blackSaturday = (clone $easter)->modify('-1 days')->format('Y-m-d');
            
            // Calculate National Heroes Day (Last Monday of August)
            $heroesDay = new \DateTime("last monday of August $year");
            $heroesDayStr = $heroesDay->format('Y-m-d');

            $yearHolidays = [
                // Fixed National Holidays
                ['date' => "$year-01-01", 'name' => 'New Year\'s Day', 'description' => 'First day of the year', 'type' => 'national'],
                ['date' => "$year-02-25", 'name' => 'People Power Anniversary', 'description' => 'EDSA Revolution commemoration', 'type' => 'national'],
                ['date' => "$year-04-09", 'name' => 'Araw ng Kagitingan', 'description' => 'Day of Valor', 'type' => 'national'],
                ['date' => "$year-05-01", 'name' => 'Labor Day', 'description' => 'International Workers\' Day', 'type' => 'national'],
                ['date' => "$year-06-12", 'name' => 'Independence Day', 'description' => 'Philippine Independence from Spain', 'type' => 'national'],
                ['date' => "$year-08-21", 'name' => 'Ninoy Aquino Day', 'description' => 'Assassination of Benigno "Ninoy" Aquino Jr.', 'type' => 'national'],
                ['date' => "$year-11-01", 'name' => 'All Saints\' Day', 'description' => 'Honoring the saints', 'type' => 'national'],
                ['date' => "$year-11-02", 'name' => 'All Souls\' Day', 'description' => 'Additional special non-working day', 'type' => 'national'],
                ['date' => "$year-11-30", 'name' => 'Bonifacio Day', 'description' => 'Birth anniversary of Andres Bonifacio', 'type' => 'national'],
                ['date' => "$year-12-08", 'name' => 'Feast of the Immaculate Conception', 'description' => 'Catholic holiday', 'type' => 'national'],
                ['date' => "$year-12-25", 'name' => 'Christmas Day', 'description' => 'Birth of Jesus Christ', 'type' => 'national'],
                ['date' => "$year-12-30", 'name' => 'Rizal Day', 'description' => 'Death anniversary of Dr. Jose Rizal', 'type' => 'national'],
                
                // Common School Holidays (Fixed)
                ['date' => "$year-12-24", 'name' => 'Christmas Eve', 'description' => 'Day before Christmas', 'type' => 'school'],
                ['date' => "$year-12-31", 'name' => 'New Year\'s Eve', 'description' => 'Last day of the year', 'type' => 'school'],
                
                // Calculated Movable Holidays
                ['date' => $maundyThursday, 'name' => 'Maundy Thursday', 'description' => 'Holy Week', 'type' => 'national'],
                ['date' => $goodFriday, 'name' => 'Good Friday', 'description' => 'Holy Week', 'type' => 'national'],
                ['date' => $blackSaturday, 'name' => 'Black Saturday', 'description' => 'Holy Week', 'type' => 'national'],
                ['date' => $heroesDayStr, 'name' => 'National Heroes Day', 'description' => 'Honoring Filipino heroes', 'type' => 'national'],
            ];

            // Add predefined movable dates if available for the year
            if (isset($movableDates[$year])) {
                $yearHolidays[] = ['date' => "$year-" . $movableDates[$year]['cny'], 'name' => 'Chinese New Year', 'description' => 'Lunar New Year', 'type' => 'national'];
                $yearHolidays[] = ['date' => "$year-" . $movableDates[$year]['fitr'], 'name' => 'Eid\'l Fitr', 'description' => 'End of Ramadan', 'type' => 'national'];
                $yearHolidays[] = ['date' => "$year-" . $movableDates[$year]['adha'], 'name' => 'Eid\'l Adha', 'description' => 'Feast of Sacrifice', 'type' => 'national'];
            }

            foreach ($yearHolidays as $holiday) {
                $cleanDate = \Carbon\Carbon::parse($holiday['date'])->format('Y-m-d');
                \Illuminate\Support\Facades\DB::table('holidays')->upsert(
                    [
                        'date'        => $cleanDate,
                        'name'        => $holiday['name'],
                        'description' => $holiday['description'],
                        'type'        => $holiday['type'],
                        'is_active'   => 1,
                        'created_by'  => $admin->id,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ],
                    ['date'], // unique key
                    ['name', 'description', 'type', 'updated_at'] // columns to update on conflict
                );
            }
        }

        $this->command->info('Default holidays for 2026-2050 seeded successfully!');
    }
}
