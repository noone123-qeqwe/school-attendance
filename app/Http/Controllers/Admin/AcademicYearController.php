<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of academic years and semesters.
     */
    public function index()
    {
        $academicYears = AcademicYear::withCount('attendances')
            ->orderBy('is_current', 'desc')
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

        return view('admin.academic_years.index', compact('academicYears', 'currentAcademicYear'));
    }

    /**
     * Store a newly created academic year in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'semester' => 'required|integer|in:1,2,3',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request) {
            $isCurrent = $request->boolean('is_current');

            if ($isCurrent) {
                AcademicYear::query()->update(['is_current' => false]);
            }

            $academicYear = AcademicYear::create([
                'name' => trim($request->name),
                'semester' => (int) $request->semester,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_current' => $isCurrent,
            ]);

            if ($isCurrent) {
                Setting::updateOrCreate(['key' => 'academic_year'], ['value' => $academicYear->name]);
                Setting::updateOrCreate(['key' => 'current_semester'], ['value' => $academicYear->semester_label]);
                Cache::forget('current_academic_year_start');
            }

            activity()
                ->performedOn($academicYear)
                ->causedBy(Auth::user())
                ->log("Created academic year: {$academicYear->name} - {$academicYear->semester_label}");
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year term created successfully.');
    }

    /**
     * Update the specified academic year in storage.
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'semester' => 'required|integer|in:1,2,3',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $academicYear) {
            $isCurrent = $request->boolean('is_current');

            if ($isCurrent) {
                AcademicYear::where('id', '!=', $academicYear->id)->update(['is_current' => false]);
            }

            $academicYear->update([
                'name' => trim($request->name),
                'semester' => (int) $request->semester,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_current' => $isCurrent,
            ]);

            if ($isCurrent) {
                Setting::updateOrCreate(['key' => 'academic_year'], ['value' => $academicYear->name]);
                Setting::updateOrCreate(['key' => 'current_semester'], ['value' => $academicYear->semester_label]);
                Cache::forget('current_academic_year_start');
            }

            activity()
                ->performedOn($academicYear)
                ->causedBy(Auth::user())
                ->log("Updated academic year: {$academicYear->name} - {$academicYear->semester_label}");
        });

        return redirect()->route('academic-years.index')->with('success', 'Academic year term updated successfully.');
    }

    /**
     * Set the specified academic year as the active current term.
     */
    public function setCurrent(AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->update(['is_current' => false]);
            $academicYear->update(['is_current' => true]);

            Setting::updateOrCreate(['key' => 'academic_year'], ['value' => $academicYear->name]);
            Setting::updateOrCreate(['key' => 'current_semester'], ['value' => $academicYear->semester_label]);
            Cache::forget('current_academic_year_start');

            activity()
                ->performedOn($academicYear)
                ->causedBy(Auth::user())
                ->log("Activated academic year: {$academicYear->name} - {$academicYear->semester_label}");
        });

        return back()->with('success', "Active term switched to {$academicYear->name} ({$academicYear->semester_label}).");
    }

    /**
     * Remove the specified academic year from storage.
     */
    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->is_current) {
            return back()->with('error', 'Cannot delete the currently active academic year. Switch active terms first.');
        }

        if ($academicYear->attendances()->exists()) {
            return back()->with('error', 'Cannot delete this academic year because attendance records are associated with it.');
        }

        $name = "{$academicYear->name} ({$academicYear->semester_label})";
        $academicYear->delete();

        activity()
            ->causedBy(Auth::user())
            ->log("Deleted academic year term: {$name}");

        return redirect()->route('academic-years.index')->with('success', 'Academic year term deleted successfully.');
    }
}
