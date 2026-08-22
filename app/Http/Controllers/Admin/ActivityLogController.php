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

        // High-level KPI Telemetry Stats (Fast & Aggregated)
        $totalLogsCount = Activity::count();
        $todayLogsCount = Activity::whereDate('created_at', today())->count();
        $authLogsCount = Activity::where(function($q) {
            $q->where('description', 'like', '%login%')
              ->orWhere('description', 'like', '%auth%')
              ->orWhere('description', 'like', '%password%')
              ->orWhere('log_name', 'auth');
        })->count();
        $mutationLogsCount = Activity::whereIn('description', ['created', 'updated', 'deleted'])->count();
        $uniqueCausersCount = Activity::whereNotNull('causer_id')->distinct('causer_id')->count('causer_id');

        // Distinct causers and actions for dynamic filter dropdowns
        $causersList = \App\Models\User::whereIn('id', Activity::whereNotNull('causer_id')->select('causer_id')->distinct()->pluck('causer_id'))
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        $actionsList = Activity::select('description')
            ->whereNotNull('description')
            ->distinct()
            ->pluck('description')
            ->sort()
            ->values();

        return view('admin.activity_logs', compact(
            'logs',
            'totalLogsCount',
            'todayLogsCount',
            'authLogsCount',
            'mutationLogsCount',
            'uniqueCausersCount',
            'causersList',
            'actionsList'
        ));
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
        } elseif ($request->filled('date_preset')) {
            if ($request->date_preset === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($request->date_preset === 'yesterday') {
                $query->whereDate('created_at', today()->subDay());
            } elseif ($request->date_preset === '7days') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($request->date_preset === '30days') {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        }

        if ($request->filled('action')) {
            $action = $request->action;
            if ($action === 'login' || $action === 'auth') {
                $query->where(function($q) {
                    $q->where('description', 'like', '%login%')
                      ->orWhere('description', 'like', '%auth%')
                      ->orWhere('log_name', 'auth');
                });
            } else {
                $query->where('description', $action);
            }
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
