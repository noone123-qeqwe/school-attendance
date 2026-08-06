<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendParentDigests extends Command
{
    protected $signature = 'app:send-parent-digests';
    protected $description = 'Send daily attendance summary digest emails to parents';

    public function handle()
    {
        $this->info('Starting parent daily digests...');
        
        $parents = \App\Models\User::where('role', 'parent')
            ->with(['children' => function($q) {
                $q->with(['attendances' => function($a) {
                    $a->whereDate('date', today())->with('subject');
                }]);
            }])
            ->get();
            
        $sentCount = 0;

        foreach ($parents as $parent) {
            $hasData = false;
            $digestData = [];

            foreach ($parent->children as $child) {
                if ($child->attendances->isNotEmpty()) {
                    $hasData = true;
                    $digestData[] = [
                        'child' => $child,
                        'attendances' => $child->attendances
                    ];
                }
            }

            if ($hasData && $parent->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($parent->email)
                        ->send(new \App\Mail\ParentDailyDigest($parent, $digestData));
                    $sentCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to send to {$parent->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("Completed sending {$sentCount} digests.");
    }
}
