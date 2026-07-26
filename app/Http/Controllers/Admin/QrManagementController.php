<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QrManagementController extends Controller
{
    public function index()
    {
        $students = \App\Models\User::where('role', 'student')->latest()->paginate(12);
        return view('admin.qr.index', compact('students'));
    }
}
