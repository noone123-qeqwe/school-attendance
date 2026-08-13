<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = \App\Models\Announcement::with('author')->latest()->paginate(10);
        return view('admin.announcements.index', compact('announcements'));
    }
}
