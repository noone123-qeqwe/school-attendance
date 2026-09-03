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
            'student_ids.*' => 'exists:users,id,role,student'
        ]);

        $students = \App\Models\User::where('role', 'student')->whereIn('id', $request->student_ids)->get();

        // Ensure QR codes are cached to disk so DomPDF does not perform blocking remote network calls
        $storageDir = storage_path('app/public/qrcodes');
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0775, true);
        }

        foreach ($students as $student) {
            $filename = 'qrcodes/student_' . $student->id . '.png';
            $fullPath = storage_path('app/public/' . $filename);

            if (!file_exists($fullPath)) {
                $qrData = $student->student_number ?? (string) $student->id;
                try {
                    $img = @file_get_contents("https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrData));
                    if ($img) {
                        @file_put_contents($fullPath, $img);
                        $student->update(['qr_code_path' => $filename]);
                    }
                } catch (\Throwable $e) {}
            } elseif (!$student->qr_code_path) {
                $student->update(['qr_code_path' => $filename]);
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.qr.pdf', compact('students'));
        return $pdf->stream('bulk-qr-codes.pdf');
    }
}
