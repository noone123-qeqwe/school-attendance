@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
    @php($today = now())

    <header class="admin-section-masthead">
        <div>
            <time class="admin-date" datetime="{{ $today->toDateString() }}">{{ $today->format('l, F j') }}</time>
            <h1>School operations</h1>
            <p>Monitor attendance trends and live QR sessions.</p>
        </div>
        <a href="{{ route('admin.academic.overview') }}" class="saas-btn saas-btn-primary">
            <i class="bi bi-mortarboard" aria-hidden="true"></i> Academic Management
        </a>
    </header>

    @if($expiredSessions > 0)
        <div class="admin-dashboard-alerts" role="status">
            <x-alert-banner class="alert-card critical" :required="true">
                <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                <span>{{ $expiredSessions }} QR session(s) have expired but are still marked active.</span>
            </x-alert-banner>
        </div>
    @endif

    <div class="admin-overview-grid">
        <section class="admin-panel" aria-labelledby="attendanceTrendTitle">
            <header class="admin-panel-heading">
                <div>
                    <h2 id="attendanceTrendTitle">Attendance trend</h2>
                    <p>Past seven days · Present or late as a share of recorded results</p>
                </div>
                <a href="{{ route('admin.reports') }}" class="admin-panel-link">Reports <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </header>
            @if(collect($dailyAttendance)->sum('recorded') === 0)
                <p class="admin-empty-state">No present, late, or absent records were logged in the past seven days.</p>
            @else
                <ol class="admin-attendance-trend" aria-label="Daily attendance rates">
                    @foreach($dailyAttendance as $day)
                        <li @class(['is-today' => $loop->last])>
                            <span class="admin-trend-value" @if($day['rate'] === null) aria-label="No records" @endif>{{ $day['rate'] === null ? '—' : $day['rate'].'%' }}</span>
                            <span class="admin-trend-track" aria-hidden="true"><span style="--attendance-rate: {{ $day['rate'] ?? 0 }}%"></span></span>
                            <time datetime="{{ $day['date']->toDateString() }}" aria-label="{{ $day['date']->format('l, F j') }}">{{ $day['date']->format('D') }}</time>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="admin-panel" aria-labelledby="adminLiveSessionsTitle">
            <header class="admin-panel-heading">
                <h2 id="adminLiveSessionsTitle">Live QR sessions</h2>
                <a href="{{ route('admin.attendance') }}" class="admin-panel-link">Attendance <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </header>
            @if($activeSessions->isEmpty())
                <p class="admin-empty-state">No QR sessions are open right now.</p>
            @else
                @if($activeSessionCount > 5)
                    <p class="admin-panel-note">Showing the five most recent of {{ $activeSessionCount }} sessions.</p>
                @endif
                <ul class="admin-data-list">
                    @foreach($activeSessions->take(5) as $session)
                        <li>
                            <span><strong>{{ $session->subject?->name ?? $session->subject_code }}</strong><small>{{ $session->creator?->name ?? 'Unknown teacher' }}</small></span>
                            <span class="admin-list-detail">{{ $session->checked_in_count }} checked in <small>{{ $session->qr_status }}</small></span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

    </div>
@endsection
