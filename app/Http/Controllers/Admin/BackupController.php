<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        $backups = \App\Models\BackupLog::latest()->paginate(10);
        return view('admin.backups.index', compact('backups'));
    }
}
