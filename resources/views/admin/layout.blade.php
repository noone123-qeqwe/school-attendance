@extends('layouts.portal')

@section('portal-title', 'Admin Panel')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">
@endpush

@section('profile-role', 'Admin')
@section('profile-detail', Auth::user()->email)

@section('role-sidebar')
    <div class="sidebar-head">
        <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
        <div class="sidebar-text">
            <div class="sidebar-brand">{{ config('app.name') }}</div>
            <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
            <div class="sidebar-portal">Admin Panel</div>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-fill"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>
        <div style="padding:6px 8px;color:rgba(255,255,255,0.35);text-transform:uppercase;font-weight:700;font-size:0.62rem;">Management</div>
        <a href="{{ route('admin.students') }}" class="nav-link {{ request()->routeIs('admin.students') || request()->routeIs('admin.student.create') || request()->routeIs('admin.student.edit') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i>
            <span class="nav-link-text">Students</span>
        </a>
        <a href="{{ route('admin.subjects') }}" class="nav-link {{ request()->routeIs('admin.subjects*') ? 'active' : '' }}">
            <i class="bi bi-book-fill"></i>
            <span class="nav-link-text">Subjects</span>
        </a>
        <div style="padding:6px 8px;color:rgba(255,255,255,0.35);text-transform:uppercase;font-weight:700;font-size:0.62rem;">Monitoring</div>
        <a href="{{ route('admin.attendance') }}" class="nav-link {{ request()->routeIs('admin.attendance*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check-fill"></i>
            <span class="nav-link-text">Attendance Logs</span>
        </a>
        <a href="{{ route('admin.activity.log') }}" class="nav-link {{ request()->routeIs('admin.activity.log*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i>
            <span class="nav-link-text">Activity Logs</span>
        </a>
        <a href="{{ route('admin.calendar') }}" class="nav-link {{ request()->routeIs('admin.calendar*') ? 'active' : '' }}">
            <i class="bi bi-calendar3-fill"></i>
            <span class="nav-link-text">Holiday Calendar</span>
        </a>
        <a href="{{ route('admin.notifications') }}" class="nav-link {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}">
            <i class="bi bi-bell-fill"></i>
            <span class="nav-link-text">Notifications</span>
        </a>
        <div style="padding:6px 8px;color:rgba(255,255,255,0.35);text-transform:uppercase;font-weight:700;font-size:0.62rem;">Account</div>
        <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
            <i class="bi bi-person-fill"></i>
            <span class="nav-link-text">My Profile</span>
        </a>
        <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <i class="bi bi-sliders"></i>
            <span class="nav-link-text">Settings</span>
        </a>
    </div>

    <div class="sidebar-footer text-center">
        <small>© {{ date('Y') }} {{ config('app.name') }}</small>
    </div>
@endsection

@section('scripts')
    @include('partials.scripts.admin')
@endsection
