@extends('layouts.app')
@section('page-title', 'Attendance QR')

@section('content')
<div class="tch-card" style="max-width:700px;margin:auto;">
    <div class="tch-card-head"><div class="tch-card-title"><i class="bi bi-qr-code me-2"></i> Student Assistant</div></div>
    <div style="padding:24px;">
        <h1 style="font-size:1.4rem;color:#f3e7cd;">{{ $session->subject?->name }}</h1>
        <p style="color:#b39b82;">{{ $session->subject?->section }} · Session ends {{ $session->session_ends_at->format('h:i A') }}</p>
        <p style="color:#b39b82;">QR generation is not available yet.</p>
    </div>
</div>
@endsection
