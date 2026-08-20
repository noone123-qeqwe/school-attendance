<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_if(auth()->user()->admin_sub_role === 'data_entry', 403, 'Unauthorized. Data entry administrators cannot view activity logs.');

        $query = $this->buildQuery($request);
        $logs = $query->paginate(20)->withQueryString();

        return view('admin.activity_logs', compact('logs'));
    }

    public function export(Request $request)
    {
        abort_if(auth()->user()->admin_sub_role === 'data_entry', 403, 'Unauthorized. Data entry administrators cannot export activity logs.');

        $logs = $this->buildQuery($request)->get();
        $filename = 'activity-logs-' . now()->format('Y-m-d-H-i-s') . '.csv';

        return Excel::download(new ActivityLogExport($logs), $filename);
    }

    private function buildQuery(Request $request)
    {
        $query = Activity::with('causer')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%")
                  ->orWhere('properties', 'like', "%{$search}%")
                  ->orWhereHas('causer', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('action')) {
            $query->where('description', $request->action);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        return $query;
    }
}
