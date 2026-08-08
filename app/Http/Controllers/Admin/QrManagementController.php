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

    public function bulkPrint(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id'
        ]);

        $students = \App\Models\User::whereIn('id', $request->student_ids)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.qr.pdf', compact('students'));
        return $pdf->stream('bulk-qr-codes.pdf');
    }
}
