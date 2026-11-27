@extends('layouts.app')
@section('page-title', 'Attendance check-in')

@section('content')
    @include('partials.student-scanner-modal')
    <noscript>
        <p class="m-3">Enable JavaScript to scan a QR code or enter an attendance code.</p>
        <a class="btn btn-outline-light m-3" href="{{ route('home') }}">Back to dashboard</a>
    </noscript>
@endsection
