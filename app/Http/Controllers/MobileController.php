<?php

namespace App\Http\Controllers;

use App\Support\SettingsNavigation;
use Illuminate\Http\Request;

class MobileController extends Controller
{
    public function home(Request $request)
    {
        return redirect()->to(SettingsNavigation::dashboardUrl($request->user(), $request->query()));
    }

    public function attendance()
    {
        return redirect()->route('student.calendar');
    }

    /**
     * Mobile QR scanner
     */
    public function scan()
    {
        return view('mobile.scan');
    }

    /**
     * Mobile profile
     */
    public function profile(Request $request)
    {
        return redirect()->route(SettingsNavigation::profileRoute($request->user()), $request->query());
    }

    // Teacher-specific mobile pages
    public function classes(Request $request)
    {
        return redirect()->route('teacher.classroom.index', $request->query());
    }

    public function students()
    {
        return redirect()->route('teacher.students');
    }

    // Parent-specific mobile pages
    public function children()
    {
        return redirect()->route('parent.link.form');
    }

    public function reports(Request $request)
    {
        return redirect()->route('parent.calendar', $request->only('child_id'));
    }

    // Admin-specific mobile pages
    public function dashboard()
    {
        return redirect()->route('admin.dashboard');
    }

    public function settings()
    {
        return auth()->user()->isSuperAdmin()
            ? redirect()->route('admin.settings')
            : redirect()->route('admin.dashboard');
    }
}
