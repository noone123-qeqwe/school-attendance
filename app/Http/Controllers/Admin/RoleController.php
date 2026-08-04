<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\User::query();
        
        if ($request->has('role') && $request->role != '') {
            $query->where('role', $request->role);
        }
        
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%')
                  ->orWhere('student_number', 'like', '%'.$request->search.'%')
                  ->orWhere('employee_id', 'like', '%'.$request->search.'%');
            });
        }
        
        $users = $query->latest()->paginate(15);
        
        return view('admin.roles.index', compact('users'));
    }

    public function update(Request $request, \App\Models\User $role)
    {
        $user = $role; // Route model binding uses 'role' as the parameter name

        $request->validate([
            'role' => 'required|in:admin,teacher,student,parent,department_head',
            'admin_sub_role' => 'nullable|in:super_admin,data_entry,auditor'
        ]);

        $data = ['role' => $request->role];
        
        // Only allow sub_roles for admins
        if ($request->role === 'admin') {
            $data['admin_sub_role'] = $request->admin_sub_role;
        } else {
            $data['admin_sub_role'] = null;
        }

        $user->update($data);

        return redirect()->route('admin.roles.index')->with('success', 'User role updated successfully.');
    }
}
