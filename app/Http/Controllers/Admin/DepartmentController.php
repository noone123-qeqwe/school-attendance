<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'sort' => 'nullable|in:newest,name']);
        $query = Department::withCount('courses');
        if (($search = trim($filters['search'] ?? '')) !== '') {
            $query->where(fn ($q) => $q->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'));
        }
        ($filters['sort'] ?? 'newest') === 'name' ? $query->orderBy('name') : $query->latest();
        $departments = $query->paginate(10)->withQueryString();
        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'required|string|max:20|unique:departments,code',
            'description' => 'nullable|string|max:1000',
        ]);

        Department::create([
            'name' => trim($request->name),
            'code' => strtoupper(trim($request->code)),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.departments.index')->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'code' => 'required|string|max:20|unique:departments,code,' . $department->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $department->update([
            'name' => trim($request->name),
            'code' => strtoupper(trim($request->code)),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return redirect()->route('admin.departments.index')->with('success', 'Department deleted successfully.');
    }
}
