<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    public function index()
    {
        return view('admin.system_health.index');
    }
}
