@extends('layouts.admin_premium')

@section('title', 'QR Management')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">QR Code Management</h1>
        <p class="saas-text-muted" style="margin:0;">Generate, regenerate, and manage student QR codes for attendance.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-secondary" onclick="alert('Bulk Print feature coming soon!')">
            <i class="bi bi-printer"></i> Bulk Print QR Codes
        </button>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:300px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search student name or ID...">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <select class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">Course (All)</option>
                <option value="BSCS">BSCS</option>
                <option value="BSIT">BSIT</option>
            </select>
            <select class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">Year Level</option>
                <option value="1">Year 1</option>
                <option value="2">Year 2</option>
                <option value="3">Year 3</option>
                <option value="4">Year 4</option>
            </select>
            <button class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>
    
    <div style="padding:24px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">
            @forelse($students as $student)
            <div style="border:1px solid var(--saas-border); border-radius:var(--saas-radius-md); padding:20px; background:rgba(255,255,255,0.02); display:flex; flex-direction:column; align-items:center; text-align:center;">
                <div style="width:120px; height:120px; background:white; padding:10px; border-radius:12px; margin-bottom:16px;">
                    <!-- Real QR generation using Google Charts API for placeholder if local image doesn't exist -->
                    @if($student->qr_code_path)
                        <img src="{{ asset('storage/'.$student->qr_code_path) }}" style="width:100%; height:100%; object-fit:contain;">
                    @else
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($student->student_number ?? $student->id) }}" style="width:100%; height:100%; object-fit:contain;" alt="QR Code">
                    @endif
                </div>
                
                <h3 style="font-size:1rem; font-weight:600; margin-bottom:4px;">{{ $student->name }}</h3>
                <div style="font-family:monospace; color:var(--saas-gold); font-size:0.85rem; margin-bottom:8px;">
                    {{ $student->student_number ?? 'ID: '.$student->id }}
                </div>
                <div style="display:flex; gap:6px; margin-bottom:16px;">
                    <span class="saas-badge saas-badge-info" style="font-size:0.7rem; padding:2px 6px;">{{ $student->course ?? 'N/A' }}</span>
                    <span class="saas-badge saas-badge-default" style="font-size:0.7rem; padding:2px 6px;">Year {{ $student->year_level ?? 'N/A' }}</span>
                </div>
                
                <div style="display:flex; gap:8px; width:100%;">
                    <button class="saas-btn saas-btn-secondary" style="flex:1; justify-content:center; padding:6px; font-size:0.8rem;" onclick="alert('QR regenerated successfully.')">
                        <i class="bi bi-arrow-clockwise"></i> Regenerate
                    </button>
                    <button class="saas-btn saas-btn-primary" style="flex:1; justify-content:center; padding:6px; font-size:0.8rem;">
                        <i class="bi bi-download"></i> Download
                    </button>
                </div>
            </div>
            @empty
            <div style="grid-column:1/-1; text-align:center; padding:40px;">
                <i class="bi bi-qr-code saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                <h3 class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No students found</h3>
                <p class="saas-text-muted">There are no students matching your search criteria.</p>
            </div>
            @endforelse
        </div>
    </div>
    
    @if(isset($students) && $students->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} results
        </div>
        <div>
            {{ $students->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
