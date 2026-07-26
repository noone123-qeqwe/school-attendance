@extends('layouts.portal')

@section('portal-title', 'Parent Portal')

@section('profile-role', 'Parent/Guardian')
@section('profile-detail', Auth::user()->email)

@section('role-sidebar')
    <div class="sidebar-head">
        <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
        <div class="sidebar-text">
            <div class="sidebar-brand">{{ config('app.name') }}</div>
            <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
            <div class="sidebar-portal">Parent Portal</div>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-nav">
        <a href="{{ route('parent.dashboard') }}" class="nav-link {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}" data-title="Dashboard">
            <i class="bi bi-grid-fill"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>
        <a href="{{ route('parent.calendar') }}" class="nav-link {{ request()->routeIs('parent.calendar') ? 'active' : '' }}" data-title="Calendar">
            <i class="bi bi-calendar-event"></i>
            <span class="nav-link-text">Calendar</span>
        </a>
        <a href="{{ route('parent.excuses') }}" class="nav-link {{ request()->routeIs('parent.excuses') ? 'active' : '' }}" data-title="Excuse Letters">
            <i class="bi bi-file-earmark-text"></i>
            <span class="nav-link-text">Excuse Letters</span>
        </a>
        <a href="{{ route('parent.messages.index') }}" class="nav-link {{ request()->routeIs('parent.messages.*') ? 'active' : '' }}" data-title="Messages">
            <i class="bi bi-chat-dots-fill"></i>
            <span class="nav-link-text">Messages</span>
        </a>
        <a href="{{ route('parent.notifications') }}" class="nav-link {{ request()->routeIs('parent.notifications') ? 'active' : '' }}" data-title="Notifications">
            <i class="bi bi-bell-fill"></i>
            <span class="nav-link-text">Notifications</span>
            @php
                $childIds = Auth::user()->children()->pluck('users.id');
                $unreadChildNotifs = \App\Models\Notification::whereIn('user_id', $childIds)->where('is_read', false)->count();
            @endphp
            @if($unreadChildNotifs > 0)
                <span style="background: #dc2626; color: white; font-size: 0.65rem; font-weight: 700; padding: 2px 7px; border-radius: 999px; margin-left: auto;">{{ $unreadChildNotifs > 99 ? '99+' : $unreadChildNotifs }}</span>
            @endif
        </a>
    </div>

    <div class="sidebar-footer text-center">
        <small>© {{ date('Y') }} {{ config('app.name') }}</small>
    </div>
@endsection
