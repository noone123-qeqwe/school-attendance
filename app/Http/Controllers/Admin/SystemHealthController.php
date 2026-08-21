<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

class SystemHealthController extends Controller
{
    public function index()
    {
        abort_if(auth()->user()->admin_sub_role !== 'super_admin' && !auth()->user()->isAdmin(), 403);
        return redirect()->route('admin.system-update.index', ['tab' => 'health']);
    }
}
