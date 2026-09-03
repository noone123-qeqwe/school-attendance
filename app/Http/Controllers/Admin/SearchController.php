<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subject;
use App\Models\Announcement;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->input('q', $request->input('query', '')));

        if ($q === '') {
            $users = collect();
            $subjects = collect();
            $announcements = collect();
        } else {
            $users = User::where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('student_number', 'like', "%{$q}%")
                ->orWhere('employee_id', 'like', "%{$q}%")
                ->limit(15)
                ->get(['id', 'name', 'email', 'role', 'student_number', 'employee_id']);

            $subjects = Subject::where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->limit(15)
                ->get(['id', 'name', 'code', 'year_level', 'semester']);

            $announcements = Announcement::where('title', 'like', "%{$q}%")
                ->orWhere('content', 'like', "%{$q}%")
                ->limit(10)
                ->get(['id', 'title', 'target_audience', 'created_at']);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'users' => $users,
                'subjects' => $subjects,
                'announcements' => $announcements,
            ]);
        }

        return view('admin.search', compact('q', 'users', 'subjects', 'announcements'));
    }
}
