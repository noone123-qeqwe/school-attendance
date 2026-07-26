<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GpsController extends Controller
{
    public function showConfig()
    {
        // Read current GPS settings from controllers
        $attendanceController = File::get(app_path('Http/Controllers/AttendanceController.php'));
        $qrController = File::get(app_path('Http/Controllers/QrAttendanceController.php'));
        
        // Extract coordinates using regex
        preg_match('/\$schoolLat = ([0-9.-]+);/', $attendanceController, $latMatch);
        preg_match('/\$schoolLng = ([0-9.-]+);/', $attendanceController, $lngMatch);
        preg_match('/\$radiusMeters = ([0-9]+);/', $attendanceController, $radiusMatch);
        
        $currentLat = $latMatch[1] ?? '14.6507';
        $currentLng = $lngMatch[1] ?? '121.0689';
        $currentRadius = $radiusMatch[1] ?? '50';
        
        return view('admin.gps-config', compact('currentLat', 'currentLng', 'currentRadius'));
    }
    
    public function updateConfig(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:500'
        ]);
        
        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = $request->radius;
        
        // Update AttendanceController
        $attendanceController = File::get(app_path('Http/Controllers/AttendanceController.php'));
        $attendanceController = preg_replace(
            '/\$schoolLat = [0-9.-]+;/',
            "\$schoolLat = {$lat};",
            $attendanceController
        );
        $attendanceController = preg_replace(
            '/\$schoolLng = [0-9.-]+;/',
            "\$schoolLng = {$lng};",
            $attendanceController
        );
        $attendanceController = preg_replace(
            '/\$radiusMeters = [0-9]+;/',
            "\$radiusMeters = {$radius};",
            $attendanceController
        );
        File::put(app_path('Http/Controllers/AttendanceController.php'), $attendanceController);
        
        // Update QrAttendanceController constants
        $qrController = File::get(app_path('Http/Controllers/QrAttendanceController.php'));
        $qrController = preg_replace(
            '/private const SCHOOL_LAT\s*=\s*[0-9.-]+;/',
            "private const SCHOOL_LAT    = {$lat};",
            $qrController
        );
        $qrController = preg_replace(
            '/private const SCHOOL_LNG\s*=\s*[0-9.-]+;/',
            "private const SCHOOL_LNG    = {$lng};",
            $qrController
        );
        $qrController = preg_replace(
            '/private const RADIUS_METERS\s*=\s*[0-9]+;/',
            "private const RADIUS_METERS = {$radius};",
            $qrController
        );
        File::put(app_path('Http/Controllers/QrAttendanceController.php'), $qrController);
        
        return redirect()->back()->with('success', 'GPS coordinates updated successfully!');
    }
    
    public function quickUpdate(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);
        
        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = 50; // Reset to normal radius
        
        // Update QrAttendanceController constants
        $qrController = File::get(app_path('Http/Controllers/QrAttendanceController.php'));
        $qrController = preg_replace(
            '/private const SCHOOL_LAT\s*=\s*[0-9.-]+;/',
            "private const SCHOOL_LAT    = {$lat};",
            $qrController
        );
        $qrController = preg_replace(
            '/private const SCHOOL_LNG\s*=\s*[0-9.-]+;/',
            "private const SCHOOL_LNG    = {$lng};",
            $qrController
        );
        $qrController = preg_replace(
            '/private const RADIUS_METERS\s*=\s*[0-9]+;/',
            "private const RADIUS_METERS = {$radius};",
            $qrController
        );
        File::put(app_path('Http/Controllers/QrAttendanceController.php'), $qrController);
        
        // Update AttendanceController
        $attendanceController = File::get(app_path('Http/Controllers/AttendanceController.php'));
        $attendanceController = preg_replace(
            '/\$schoolLat = [0-9.-]+;/',
            "\$schoolLat = {$lat};",
            $attendanceController
        );
        $attendanceController = preg_replace(
            '/\$schoolLng = [0-9.-]+;/',
            "\$schoolLng = {$lng};",
            $attendanceController
        );
        $attendanceController = preg_replace(
            '/\$radiusMeters = [0-9]+;/',
            "\$radiusMeters = {$radius};",
            $attendanceController
        );
        File::put(app_path('Http/Controllers/AttendanceController.php'), $attendanceController);
        
        return response()->json(['success' => true, 'message' => 'Coordinates updated successfully!']);
    }
}