<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('author')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('audience') && $request->audience !== 'all') {
            $audience = match(strtolower($request->audience)) {
                'student' => 'Student',
                'teacher' => 'Teacher',
                'parent' => 'Parent',
                default => $request->audience,
            };
            $query->where('target_audience', $audience);
        }

        $announcements = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => Announcement::count(),
            'all' => Announcement::where('target_audience', 'All')->count(),
            'student' => Announcement::where('target_audience', 'Student')->count(),
            'teacher' => Announcement::where('target_audience', 'Teacher')->count(),
        ];

        return view('admin.announcements.index', compact('announcements', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_role' => 'nullable|string|in:all,student,teacher,parent',
        ]);

        $audience = match(strtolower($request->target_role ?? 'all')) {
            'student' => 'Student',
            'teacher' => 'Teacher',
            'parent' => 'Parent',
            default => 'All',
        };

        Announcement::create([
            'title' => trim($request->title),
            'content' => trim($request->content),
            'target_audience' => $audience,
            'author_id' => Auth::id(),
            'scheduled_for' => now(),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement broadcasted successfully.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_role' => 'nullable|string|in:all,student,teacher,parent',
        ]);

        $audience = match(strtolower($request->target_role ?? 'all')) {
            'student' => 'Student',
            'teacher' => 'Teacher',
            'parent' => 'Parent',
            default => 'All',
        };

        $announcement->update([
            'title' => trim($request->title),
            'content' => trim($request->content),
            'target_audience' => $audience,
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted successfully.');
    }
}
