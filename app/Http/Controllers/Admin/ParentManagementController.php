<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\ParentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ParentManagementController extends Controller
{
    /**
     * Display a listing of parent/guardian accounts.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'parent')->with('children');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('children', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('student_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'linked') {
                $query->has('children');
            } elseif ($request->status === 'unlinked') {
                $query->doesntHave('children');
            }
        }

        $parents = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => User::where('role', 'parent')->count(),
            'active' => User::where('role', 'parent')->where('is_active', true)->count(),
            'linked' => User::where('role', 'parent')->has('children')->count(),
            'unlinked' => User::where('role', 'parent')->doesntHave('children')->count(),
        ];

        // All active students for linking modal
        $students = User::where('role', 'student')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'student_number', 'course', 'year_level', 'section']);

        return view('admin.parents.index', compact('parents', 'stats', 'students'));
    }

    /**
     * Store a newly created parent account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (!OtpService::isValidGmailFormat((string) $value)) {
                        $fail('The email must be a valid Gmail address (e.g., username@gmail.com).');
                    }
                },
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'student_id' => 'nullable',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'nullable|exists:users,id',
            'student_number' => 'nullable|string|max:255',
        ]);

        $password = $request->filled('password') ? $request->password : 'Parent@' . date('Y');

        $parent = User::create([
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'phone' => $request->phone ? trim($request->phone) : null,
            'password' => Hash::make($password),
            'role' => 'parent',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $studentIdsToLink = [];

        // 1. Array of student_ids
        if ($request->filled('student_ids') && is_array($request->student_ids)) {
            foreach ($request->student_ids as $sid) {
                if ($sid) $studentIdsToLink[] = (int) $sid;
            }
        }

        // 2. Single student_id
        if ($request->filled('student_id')) {
            $studentIdsToLink[] = (int) $request->student_id;
        }

        // 3. String student_number(s)
        if ($request->filled('student_number')) {
            $rawNumbers = preg_split('/[,\s]+/', trim((string) $request->student_number), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($rawNumbers as $num) {
                $st = User::where('role', 'student')->where(function($q) use ($num) {
                    $q->where('student_number', $num)
                      ->orWhere('student_number', ltrim($num, '0'))
                      ->orWhere('id', $num);
                })->first();
                if ($st) {
                    $studentIdsToLink[] = $st->id;
                }
            }
        }

        $studentIdsToLink = array_values(array_unique(array_filter($studentIdsToLink)));
        $linkedStudentNames = [];

        foreach ($studentIdsToLink as $sid) {
            $student = User::where('role', 'student')->find($sid);
            if ($student) {
                DB::table('parent_student')->insertOrIgnore([
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (empty($student->guardian_email)) {
                    $student->update(['guardian_email' => $parent->email]);
                }

                $linkedStudentNames[] = $student->name;

                try {
                    \App\Models\Notification::create([
                        'user_id' => $student->id,
                        'sent_by' => auth()->id() ?? $parent->id,
                        'type'    => 'parent_linked',
                        'message' => "An administrator connected parent/guardian {$parent->name} ({$parent->email}) to your profile.",
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        $linkNotice = !empty($linkedStudentNames)
            ? " and connected to " . implode(', ', $linkedStudentNames)
            : "";

        return redirect()->route('admin.parents.index')->with('success', "Parent account '{$parent->name}' registered successfully{$linkNotice}. Default password is '{$password}'.");
    }

    /**
     * Update an existing parent account.
     */
    public function update(Request $request, User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($parent->id),
                function ($attribute, $value, $fail) {
                    if (!OtpService::isValidGmailFormat((string) $value)) {
                        $fail('The email must be a valid Gmail address (e.g., username@gmail.com).');
                    }
                },
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'student_id' => 'nullable|exists:users,id',
            'student_number' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'phone' => $request->phone ? trim($request->phone) : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $parent->update($data);

        // Connect additional student if provided
        $studentToLink = null;
        if ($request->filled('student_id')) {
            $studentToLink = User::where('role', 'student')->find($request->student_id);
        } elseif ($request->filled('student_number')) {
            $num = trim($request->student_number);
            $studentToLink = User::where('role', 'student')->where(function($q) use ($num) {
                $q->where('student_number', $num)
                  ->orWhere('student_number', ltrim($num, '0'))
                  ->orWhere('id', $num);
            })->first();
        }

        $linkNotice = '';
        if ($studentToLink) {
            DB::table('parent_student')->insertOrIgnore([
                'parent_id' => $parent->id,
                'student_id' => $studentToLink->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (empty($studentToLink->guardian_email)) {
                $studentToLink->update(['guardian_email' => $parent->email]);
            }
            $linkNotice = " and connected to student {$studentToLink->name}";
        }

        return redirect()->route('admin.parents.index')->with('success', "Parent account '{$parent->name}' updated successfully{$linkNotice}.");
    }

    /**
     * Deactivate a parent account.
     */
    public function deactivate(User $parent)
    {
        abort_unless($parent->role === 'parent', 404);
        $parent->update(['is_active' => false]);

        return redirect()->route('admin.parents.index')->with('success', "Parent account '{$parent->name}' has been deactivated.");
    }

    /**
     * Reactivate a parent account.
     */
    public function reactivate($id)
    {
        $parent = User::where('role', 'parent')->findOrFail($id);
        $parent->update(['is_active' => true]);

        return redirect()->route('admin.parents.index')->with('success', "Parent account '{$parent->name}' has been reactivated.");
    }

    /**
     * Delete a parent account.
     */
    public function destroy(User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        // Remove linked pivot records
        DB::table('parent_student')->where('parent_id', $parent->id)->delete();
        $name = $parent->name;
        $parent->delete();

        return redirect()->route('admin.parents.index')->with('success', "Parent account '{$name}' deleted successfully.");
    }

    /**
     * Link a student to a parent.
     */
    public function linkStudent(Request $request, User $parent, ParentService $parentService)
    {
        abort_unless($parent->role === 'parent', 404);

        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $student = User::where('role', 'student')->findOrFail($request->student_id);

        try {
            $parentService->linkDirectlyByAdmin(auth()->user(), $parent, $student);
            return redirect()->route('admin.parents.index')->with('success', "Student '{$student->name}' linked to parent '{$parent->name}' successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.parents.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Unlink a student from a parent.
     */
    public function unlinkStudent(User $parent, User $student, ParentService $parentService)
    {
        abort_unless($parent->role === 'parent', 404);
        abort_unless($student->role === 'student', 404);

        try {
            $parentService->unlink(auth()->user(), $parent, $student);
            return redirect()->route('admin.parents.index')->with('success', "Student '{$student->name}' unlinked from parent '{$parent->name}' successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.parents.index')->with('error', $e->getMessage());
        }
    }
}
