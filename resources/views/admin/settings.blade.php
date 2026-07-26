@extends('layouts.admin_premium')

@section('title', 'System Settings')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Settings</h1>
        <p class="saas-text-muted" style="margin:0;">Configure global system parameters and preferences.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button type="submit" form="settingsForm" class="saas-btn saas-btn-primary">
            <i class="bi bi-save"></i> Save Changes
        </button>
    </div>
</div>

<div style="display:grid; grid-template-columns:minmax(0, 1fr) 300px; gap:24px;">
    
    <!-- Main Settings Form -->
    <div class="saas-card" style="padding:24px;">
        <form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            
            <h3 class="saas-heading saas-heading-sm" style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-building saas-text-muted"></i> Institution Details
            </h3>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="saas-form-group">
                    <label class="saas-label">School / Institution Name</label>
                    <input type="text" name="school_name" class="saas-input" value="{{ env('APP_NAME', 'Smart Classroom System') }}">
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Abbreviation / Short Name</label>
                    <input type="text" name="school_short_name" class="saas-input" value="SCS">
                </div>
            </div>
            
            <div class="saas-form-group" style="margin-bottom:32px;">
                <label class="saas-label">System Subtitle / Tagline</label>
                <input type="text" name="school_subtitle" class="saas-input" value="{{ env('APP_SUBTITLE', 'QR, GPS & Biometric Attendance') }}">
            </div>
            
            <hr style="border:0; border-top:1px solid var(--saas-border); margin:0 0 32px 0;">
            
            <h3 class="saas-heading saas-heading-sm" style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-clock-history saas-text-muted"></i> Academic & Attendance
            </h3>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="saas-form-group">
                    <label class="saas-label">Current Academic Year</label>
                    <select name="academic_year" class="saas-input saas-select">
                        <option value="2025-2026" selected>2025-2026</option>
                        <option value="2026-2027">2026-2027</option>
                    </select>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Current Semester</label>
                    <select name="current_semester" class="saas-input saas-select">
                        <option value="1">1st Semester</option>
                        <option value="2" selected>2nd Semester</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="saas-form-group">
                    <label class="saas-label">Late Threshold (Minutes)</label>
                    <input type="number" name="late_threshold" class="saas-input" value="15">
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Students arriving after this time are marked Late.</p>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Absent Threshold (Minutes)</label>
                    <input type="number" name="absent_threshold" class="saas-input" value="45">
                    <p class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">Students arriving after this time are marked Absent.</p>
                </div>
            </div>
            
            <hr style="border:0; border-top:1px solid var(--saas-border); margin:0 0 32px 0;">
            
            <h3 class="saas-heading saas-heading-sm" style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-bell saas-text-muted"></i> Notifications
            </h3>
            
            <label style="display:flex; align-items:center; gap:12px; margin-bottom:12px; cursor:pointer;">
                <input type="checkbox" name="email_notifications" checked style="accent-color:var(--saas-primary); width:16px; height:16px;">
                <div>
                    <div style="font-weight:500; font-size:0.9rem;">Enable Email Notifications</div>
                    <div class="saas-text-muted" style="font-size:0.75rem;">Send automated emails to parents for absences.</div>
                </div>
            </label>
            
            <label style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                <input type="checkbox" name="sms_notifications" style="accent-color:var(--saas-primary); width:16px; height:16px;">
                <div>
                    <div style="font-weight:500; font-size:0.9rem;">Enable SMS Gateway</div>
                    <div class="saas-text-muted" style="font-size:0.75rem;">Requires active Twilio/Vonage integration.</div>
                </div>
            </label>
            
        </form>
    </div>
    
    <!-- Sidebar Settings/Info -->
    <div>
        <div class="saas-card" style="padding:20px; margin-bottom:20px;">
            <h4 class="saas-heading saas-heading-sm" style="margin-bottom:12px;">System Information</h4>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span class="saas-text-muted" style="font-size:0.85rem;">Version</span>
                <span style="font-size:0.85rem; font-weight:600;">v2.0.0 (SaaS Edition)</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span class="saas-text-muted" style="font-size:0.85rem;">Laravel</span>
                <span style="font-size:0.85rem; font-weight:600;">v{{ app()->version() }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span class="saas-text-muted" style="font-size:0.85rem;">PHP</span>
                <span style="font-size:0.85rem; font-weight:600;">v{{ phpversion() }}</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span class="saas-text-muted" style="font-size:0.85rem;">Timezone</span>
                <span style="font-size:0.85rem; font-weight:600;">{{ config('app.timezone') }}</span>
            </div>
        </div>
        
        <div class="saas-card" style="padding:20px; border-color:rgba(239,68,68,0.2);">
            <h4 class="saas-heading saas-heading-sm" style="margin-bottom:12px; color:var(--saas-danger);">Danger Zone</h4>
            <p class="saas-text-muted" style="font-size:0.8rem; margin-bottom:16px;">Clearing the cache may log out active sessions and temporary slow down the system.</p>
            <button class="saas-btn saas-btn-secondary" style="width:100%; justify-content:center; color:var(--saas-danger); border-color:var(--saas-danger); background:transparent;">
                <i class="bi bi-trash"></i> Clear System Cache
            </button>
        </div>
    </div>
</div>
@endsection
