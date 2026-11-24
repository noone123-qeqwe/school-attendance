@extends('layouts.app')
@section('page-title')
    <span class="d-none d-md-inline">Student Assistant</span><span class="d-md-none">Assistant</span>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-assistant.css') }}?v={{ filemtime(public_path('css/student-assistant.css')) }}">
@endpush

@section('content')
<div class="assistant-workspace">
    @yield('assistant-content')
</div>
@endsection
