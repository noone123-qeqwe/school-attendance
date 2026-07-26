<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = \App\Models\Department::latest()->paginate(10);
        return view('admin.departments.index', compact('departments'));
    }
}
