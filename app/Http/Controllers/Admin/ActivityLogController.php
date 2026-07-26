<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();

        // Optional filtering by log_name or causer_id
        if ($request->has('log_name') && $request->log_name != '') {
            $query->where('log_name', $request->log_name);
        }

        if ($request->has('causer_id') && $request->causer_id != '') {
            $query->where('causer_id', $request->causer_id);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.activity_logs', compact('logs'));
    }
}
