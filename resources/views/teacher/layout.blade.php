@extends('layouts.portal')

@section('portal-title', 'Teacher Panel')

@section('portal-title', 'Teacher Panel')

@section('profile-role', 'Teacher')
@section('profile-detail', Auth::user()->email)

@section('role-sidebar')
    <div class="sidebar-head">
        <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
        <div class="sidebar-text">
            <div class="sidebar-brand">{{ config('app.name') }}</div>
            <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
            <div class="sidebar-portal">Teacher Panel</div>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-nav">
        <a href="{{ route('teacher.dashboard') }}" class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-fill"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>

        <div class="sidebar-section-label">Account</div>
        <a href="{{ route('teacher.profile') }}" class="nav-link {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
            <i class="bi bi-person-fill"></i>
            <span class="nav-link-text">My Profile</span>
        </a>

        <div class="sidebar-section-label">Academics</div>
        <a href="{{ route('teacher.classroom.index') }}" class="nav-link {{ request()->routeIs('teacher.classroom*') ? 'active' : '' }}">
            <i class="bi bi-journal-album"></i>
            <span class="nav-link-text">My Classes</span>
        </a>

        <div class="sidebar-section-label">Attendance Management</div>
        <a href="{{ route('teacher.absent') }}" class="nav-link {{ request()->routeIs('teacher.absent*') ? 'active' : '' }}">
            <i class="bi bi-person-x-fill"></i>
            <span class="nav-link-text">Absent Report</span>
        </a>
        <a href="{{ route('teacher.excuse.reviews') }}" class="nav-link {{ request()->routeIs('teacher.excuse*') ? 'active' : '' }}">
            <i class="bi bi-file-text-fill"></i>
            <span class="nav-link-text">Excuse Reviews</span>
        </a>

        <div class="sidebar-section-label">Communication & Insight</div>
        <a href="{{ route('teacher.calendar') }}" class="nav-link {{ request()->routeIs('teacher.calendar*') ? 'active' : '' }}">
            <i class="bi bi-calendar-event"></i>
            <span class="nav-link-text">Holiday Calendar</span>
        </a>
        <a href="{{ route('teacher.notifications') }}" class="nav-link {{ request()->routeIs('teacher.notifications*') ? 'active' : '' }}">
            <i class="bi bi-bell-fill"></i>
            <span class="nav-link-text">Notifications</span>
        </a>
    </div>

    <div class="sidebar-footer text-center">
        <small>© {{ date('Y') }} {{ config('app.name') }}</small>
    </div>
@endsection

@section('mobile-bottom-nav')
    <a href="{{ route('teacher.dashboard') }}" class="mbn-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('teacher.classroom.index') }}" class="mbn-item {{ request()->routeIs('teacher.classroom*') ? 'active' : '' }}">
        <i class="bi bi-journal-album"></i>
        <span>Classes</span>
    </a>
    <a href="{{ route('teacher.absent') }}" class="mbn-item {{ request()->routeIs('teacher.absent*') ? 'active' : '' }}">
        <i class="bi bi-person-x-fill"></i>
        <span>Absent</span>
    </a>
    <a href="{{ route('teacher.excuse.reviews') }}" class="mbn-item {{ request()->routeIs('teacher.excuse*') ? 'active' : '' }}">
        @if(isset($pendingExcuses) && $pendingExcuses > 0)<span class="mbn-badge">{{ $pendingExcuses > 9 ? '9+' : $pendingExcuses }}</span>@endif
        <i class="bi bi-file-text-fill"></i>
        <span>Excuses</span>
    </a>
    <a href="{{ route('teacher.profile') }}" class="mbn-item {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
        <i class="bi bi-person-fill"></i>
        <span>Profile</span>
    </a>
@endsection

@section('scripts')
    @include('partials.scripts.teacher')
@endsection
