@extends('layouts.app')
@section('page-title', 'Subjects PDF Preview')

@section('content')
<a href="{{ route('admin.subjects', request()->query()) }}" style="display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#64748b;text-decoration:none;padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;margin-bottom:20px;" onmouseover="this.style.color='#800000';this.style.borderColor='#800000';" onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
    <i class="bi bi-arrow-left"></i> Back to Subjects
</a>

<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
            PDF Preview: Subjects List
            @if(array_filter($filters))
            <span style="background:#fff0f3;color:#6b0020;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #f5c6cf;">
                Filtered Results
            </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.subjects.pdf', request()->query()) }}" class="adm-btn adm-btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-download"></i> Download PDF
            </a>
        </div>
    </div>

    @include('admin.subjects._report', ['forPdf' => false])
