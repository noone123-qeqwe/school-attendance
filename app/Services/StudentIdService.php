<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StudentIdService
{
    /**
     * Generate the next unique Student ID atomically and safely.
     * Format: YYYY + sequential number (e.g., 20260001, 20260002).
     *
     * @param string|null $year Specific 4-digit year, defaults to current calendar year
     * @return string
     */
    public function generateNextId(?string $year = null): string
    {
        $year = $year ?: date('Y');

        return DB::transaction(function () use ($year) {
            $hasSequenceTable = Schema::hasTable('student_id_sequences');

            if ($hasSequenceTable) {
                // Acquire pessimistic row lock on the sequence record for the given year
                $sequenceRecord = DB::table('student_id_sequences')
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$sequenceRecord) {
                    // Initialize sequence by scanning users table for maximum existing sequence
                    $maxExisting = $this->resolveMaxSequenceFromUsers($year);
                    $nextSeq = $maxExisting + 1;
                    $candidateId = $this->formatStudentId($year, $nextSeq);

                    // Ensure candidate ID does not collide with any pre-existing or imported accounts
                    while (User::withTrashed()->where('student_number', $candidateId)->exists()) {
                        $nextSeq++;
                        $candidateId = $this->formatStudentId($year, $nextSeq);
                    }

                    DB::table('student_id_sequences')->insert([
                        'year'          => $year,
                        'last_sequence' => $nextSeq,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    Log::info("Initialized student ID sequence for year {$year} at {$candidateId}");
                    return $candidateId;
                }

                // Row already exists: increment sequence
                $nextSeq = ((int) $sequenceRecord->last_sequence) + 1;
                $candidateId = $this->formatStudentId($year, $nextSeq);

                // Guarantee absolute uniqueness across all active and trashed records
                while (User::withTrashed()->where('student_number', $candidateId)->exists()) {
                    $nextSeq++;
                    $candidateId = $this->formatStudentId($year, $nextSeq);
                }

                DB::table('student_id_sequences')
                    ->where('year', $year)
                    ->update([
                        'last_sequence' => $nextSeq,
                        'updated_at'    => now(),
                    ]);

                return $candidateId;
            }

            // Fallback if sequence table is not present: lock on users table
            $yearPrefix = strlen($year) === 4 ? substr($year, -2) : $year;
            $existingNumbers = User::withTrashed()
                ->where(function ($q) use ($year, $yearPrefix) {
                    $q->where('student_number', 'LIKE', $year . '%')
                      ->orWhere('student_number', 'LIKE', $yearPrefix . '%');
                })
                ->lockForUpdate()
                ->pluck('student_number');

            $maxSeq = 0;
            foreach ($existingNumbers as $numStr) {
                $numStr = (string) $numStr;
                if (str_starts_with($numStr, $yearPrefix)) {
                    $suffix = substr($numStr, strlen($yearPrefix));
                    if (ctype_digit($suffix)) {
                        $val = (int) $suffix;
                        if ($val > $maxSeq) {
                            $maxSeq = $val;
                        }
                    }
                } elseif (str_starts_with($numStr, $year)) {
                    $suffix = substr($numStr, strlen($year));
                    if (ctype_digit($suffix)) {
                        $val = (int) $suffix;
                        if ($val > $maxSeq) {
                            $maxSeq = $val;
                        }
                    }
                }
            }

            $nextSeq = $maxSeq + 1;
            $candidateId = $this->formatStudentId($year, $nextSeq);

            while (User::withTrashed()->where('student_number', $candidateId)->exists()) {
                $nextSeq++;
                $candidateId = $this->formatStudentId($year, $nextSeq);
            }

            return $candidateId;
        });
    }

    /**
     * Preview the next Student ID without advancing the sequence or locking rows.
     * Useful for UI displays (e.g. create form placeholder).
     *
     * @param string|null $year
     * @return string
     */
    public function previewNextId(?string $year = null): string
    {
        $year = $year ?: date('Y');

        if (Schema::hasTable('student_id_sequences')) {
            $record = DB::table('student_id_sequences')->where('year', $year)->first();
            if ($record) {
                $seq = ((int) $record->last_sequence) + 1;
                $candidate = $this->formatStudentId($year, $seq);
                while (User::withTrashed()->where('student_number', $candidate)->exists()) {
                    $seq++;
                    $candidate = $this->formatStudentId($year, $seq);
                }
                return $candidate;
            }
        }

        $maxExisting = $this->resolveMaxSequenceFromUsers($year);
        $candidate = $this->formatStudentId($year, $maxExisting + 1);

        while (User::withTrashed()->where('student_number', $candidate)->exists()) {
            $maxExisting++;
            $candidate = $this->formatStudentId($year, $maxExisting + 1);
        }

        return $candidate;
    }

    /**
     * Format year and sequence number into standard 7-character school format.
     * E.g. year=2026, seq=1 => 2600001 (exactly 7 characters)
     */
    public function formatStudentId(string $year, int $sequence): string
    {
        $prefix = strlen($year) === 4 ? substr($year, -2) : $year;
        return $prefix . sprintf('%05d', $sequence);
    }

    /**
     * Inspect users table to find highest integer sequence for given year.
     */
    protected function resolveMaxSequenceFromUsers(string $year): int
    {
        $yearPrefix = strlen($year) === 4 ? substr($year, -2) : $year;

        $existingNumbers = User::withTrashed()
            ->where(function ($q) use ($year, $yearPrefix) {
                $q->where('student_number', 'LIKE', $year . '%')
                  ->orWhere('student_number', 'LIKE', $yearPrefix . '%');
            })
            ->pluck('student_number');

        $maxSeq = 0;
        foreach ($existingNumbers as $numStr) {
            $numStr = (string) $numStr;
            if (str_starts_with($numStr, $yearPrefix)) {
                $suffix = substr($numStr, strlen($yearPrefix));
                if (ctype_digit($suffix)) {
                    $val = (int) $suffix;
                    if ($val > $maxSeq) {
                        $maxSeq = $val;
                    }
                }
            } elseif (str_starts_with($numStr, $year)) {
                $suffix = substr($numStr, strlen($year));
                if (ctype_digit($suffix)) {
                    $val = (int) $suffix;
                    if ($val > $maxSeq) {
                        $maxSeq = $val;
                    }
                }
            }
        }

        return $maxSeq;
    }
}
