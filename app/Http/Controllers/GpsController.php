<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GpsController extends Controller
{
    public function showConfig()
    {
        $currentLat = \App\Models\Setting::get('gps_lat', '14.6507');
        $currentLng = \App\Models\Setting::get('gps_lng', '121.0689');
        $currentRadius = \App\Models\Setting::get('gps_radius', '50');
        
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
        
        \App\Models\Setting::updateOrCreate(['key' => 'gps_lat'], ['value' => $lat]);
        \App\Models\Setting::updateOrCreate(['key' => 'gps_lng'], ['value' => $lng]);
        \App\Models\Setting::updateOrCreate(['key' => 'gps_radius'], ['value' => $radius]);
        
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
        
        \App\Models\Setting::updateOrCreate(['key' => 'gps_lat'], ['value' => $lat]);
        \App\Models\Setting::updateOrCreate(['key' => 'gps_lng'], ['value' => $lng]);
        \App\Models\Setting::updateOrCreate(['key' => 'gps_radius'], ['value' => $radius]);
        
        return response()->json(['success' => true, 'message' => 'Coordinates updated successfully!']);
    }
}