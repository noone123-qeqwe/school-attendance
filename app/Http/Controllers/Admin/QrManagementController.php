<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QrManagementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'course' => 'nullable|string|max:50', 'year' => 'nullable|integer|between:1,5']);
        $query = \App\Models\User::where('role', 'student');
        if (($search = trim($filters['search'] ?? '')) !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('student_number', 'like', '%'.$search.'%'));
        }
        if (!empty($filters['course'])) $query->where('course', $filters['course']);
        if (!empty($filters['year'])) $query->where('year_level', $filters['year']);
        $students = $query->latest()->paginate(12)->withQueryString();
        $courses = \App\Models\Course::pluck('code')
            ->merge(\App\Models\User::where('role', 'student')->whereNotNull('course')->distinct()->pluck('course'))
            ->filter()->unique()->sort()->values();
        $qrImages = [];
        foreach ($students as $student) {
            $path = $this->qrImagePath($student);
            $qrImages[$student->id] = Storage::disk('public')->exists($path)
                ? public_storage_url($path)
                : 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.urlencode($student->student_number ?? (string) $student->id);
        }
        return view('admin.qr.index', compact('students', 'courses', 'qrImages'));
    }

    public function bulkPrint(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1|max:100',
            'student_ids.*' => 'required|integer|distinct|exists:users,id,role,student',
        ]);
        $students = User::where('role', 'student')->whereIn('id', $validated['student_ids'])->get();
        $disk = Storage::disk('public');
        $qrImages = [];

        foreach ($students as $student) {
            $path = $this->qrImagePath($student);
            try {
                if (!$disk->exists($path)) {
                    $response = Http::connectTimeout(3)->timeout(6)->get('https://api.qrserver.com/v1/create-qr-code/', [
                        'size' => '150x150', 'data' => $student->student_number ?? (string) $student->id,
                    ]);
                    $image = $response->body();
                    $info = @getimagesizefromstring($image);
                    if (!$response->successful() || !$info || $info[2] !== IMAGETYPE_PNG || !$disk->put($path, $image)) {
                        return back()->with('error', 'Unable to prepare the QR codes. Please try printing again.');
                    }
                }
                $qrImages[$student->id] = 'data:image/png;base64,'.base64_encode($disk->get($path));
            } catch (\Throwable $exception) {
                report($exception);
                return back()->with('error', 'QR printing is temporarily unavailable. Please try again.');
            }
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.qr.pdf', compact('students', 'qrImages'))->stream('bulk-qr-codes.pdf');
    }

    private function qrImagePath(User $student): string
    {
        $value = $student->student_number ?? (string) $student->id;
        return 'qrcodes/student_'.$student->id.'_'.substr(hash('sha256', $value), 0, 16).'.png';
    }
}
