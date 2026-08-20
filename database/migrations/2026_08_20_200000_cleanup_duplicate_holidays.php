<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fetch all holidays
        $holidays = DB::table('holidays')->orderBy('id', 'asc')->get();
        
        $grouped = [];
        foreach ($holidays as $h) {
            $cleanDate = substr(trim($h->date), 0, 10);
            $grouped[$cleanDate][] = $h;
        }

        $toDelete = [];
        $toUpdate = [];

        foreach ($grouped as $cleanDate => $records) {
            // Prefer record with valid clean date or latest record
            $keeper = null;
            foreach ($records as $r) {
                if ($keeper === null || $r->id > $keeper->id) {
                    $keeper = $r;
                }
            }

            if ($keeper) {
                $toUpdate[$keeper->id] = $cleanDate;
                foreach ($records as $r) {
                    if ($r->id !== $keeper->id) {
                        $toDelete[] = $r->id;
                    }
                }
            }
        }

        // 2. Delete duplicates
        if (!empty($toDelete)) {
            DB::table('holidays')->whereIn('id', $toDelete)->delete();
        }

        // 3. Normalize remaining dates to strict YYYY-MM-DD
        foreach ($toUpdate as $id => $date) {
            DB::table('holidays')->where('id', $id)->update(['date' => $date]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: duplicate cleanup is non-reversible
    }
};
