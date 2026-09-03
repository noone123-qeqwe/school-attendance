<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('author')->latest()->paginate(10);
        return view('admin.announcements.index', compact('announcements'));
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
