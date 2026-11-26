@extends('layouts.app')
@section('page-title', 'Subjects PDF Preview')

@section('content')
<x-back-link :href="route('admin.subjects', request()->query())" label="Back to Subjects" variant="admin" style="margin-bottom:20px;" />

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
