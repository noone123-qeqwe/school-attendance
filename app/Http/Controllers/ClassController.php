<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get user's year & semester
        $year = (int) $user->year_level;
        $semester = (int) $user->semester;

        // Fetch subjects based on year & semester
        $subjects = Subject::where('year_level', $year)
            ->where('semester', $semester)
            ->get();

        // SEND to blade
        return view('classes.index', compact('subjects'));
    }
}