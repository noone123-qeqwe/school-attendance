@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
    @php
        $isAuditor = auth()->user()->admin_sub_role === 'auditor';
        $today = $dailyAttendance[6];
        $yesterday = $dailyAttendance[5];
        $rateDifference = $today['rate'] !== null && $yesterday['rate'] !== null
            ? $today['rate'] - $yesterday['rate']
            : null;
        $reviewQueue = [
            ['Excuse reviews', route('admin.excuses'), $pendingExcuses, 'bi-file-earmark-text'],
            ['Attendance corrections', route('admin.corrections'), $pendingCorrections, 'bi-pencil-square'],
        ];
        if (!$isAuditor) {
            array_unshift($reviewQueue, ['Student registrations', route('admin.students', ['status' => 'pending']), $pendingRegistrations, 'bi-person-check']);
        }
    @endphp

    <header class="admin-section-masthead">
        <div>
            <time class="admin-date" datetime="{{ $today['date']->toDateString() }}">{{ $today['date']->format('l, F j') }}</time>
            <h1>School operations</h1>
            <p>Track attendance, review pending work, and see what’s happening at school.</p>
        </div>
        <a href="{{ route('admin.academic.overview') }}" class="saas-btn saas-btn-primary">
            <i class="bi bi-mortarboard" aria-hidden="true"></i> Academic Management
        </a>
    </header>

    <dl class="admin-metrics" aria-label="Today’s attendance and activity">
        <div>
            <dt>Attendance rate</dt>
            <dd>
                {{ $today['rate'] === null ? '—' : $today['rate'].'%' }}
                @if($rateDifference !== null)
                    <small>{{ sprintf('%+d', $rateDifference) }} percentage points vs yesterday</small>
                @elseif($today['rate'] === null)
                    <small>No attendance recorded today</small>
                @endif
            </dd>
        </div>
        <div><dt>Present today</dt><dd>{{ number_format($totalPresent) }} <small>{{ number_format($totalLate) }} late</small></dd></div>
        <div><dt>Absent today</dt><dd>{{ number_format($totalAbsent) }}</dd></div>
        <div><dt>Live QR sessions</dt><dd>{{ number_format($activeSessionCount) }}</dd></div>
    </dl>

    @if($systemAlerts->contains('severity', 'critical'))
        <div class="admin-dashboard-alerts" role="status">
            @foreach($systemAlerts->where('severity', 'critical') as $alert)
                <div class="alert-card critical">
                    <i class="bi {{ $alert->icon }}" aria-hidden="true"></i>
                    <span>{{ $alert->message }}</span>
                </div>
            @endforeach
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

        <section class="admin-panel" aria-labelledby="adminReviewTitle">
            <header class="admin-panel-heading">
                <div>
                    <h2 id="adminReviewTitle">{{ $isAuditor ? 'Attendance review' : 'Needs attention' }}</h2>
                    <p>Items awaiting review</p>
                </div>
            </header>
            <ul class="admin-queue">
                @foreach($reviewQueue as [$label, $url, $count, $icon])
                    <li>
                        <a href="{{ $url }}">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i>
                            <span>{{ $label }}</span>
                            <strong @class(['has-pending' => $count > 0]) aria-label="{{ $count }} pending">{{ number_format($count) }}</strong>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
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

        <section class="admin-panel" id="at-risk-section" aria-labelledby="adminRiskTitle">
            <header class="admin-panel-heading">
                <h2 id="adminRiskTitle">Students with low attendance</h2>
                <a href="{{ route('admin.early-warnings') }}" class="admin-panel-link">Warnings <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </header>
            @if($atRiskStudents->isEmpty())
                <p class="admin-empty-state">No students with low attendance in the current results.</p>
            @else
                <ul class="admin-data-list">
                    @foreach($atRiskStudents->take(5) as $student)
                        <li>
                            <span><a href="{{ route('admin.student', $student) }}">{{ $student->name }}</a><small>{{ $student->course }}</small></span>
                            <span class="admin-risk {{ $student->attendance_rate < 70 ? 'is-critical' : '' }}">{{ $student->attendance_rate }}%</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="admin-panel admin-panel-wide" aria-labelledby="adminRecentTitle">
            <header class="admin-panel-heading">
                <h2 id="adminRecentTitle">Recent notifications</h2>
                <a href="{{ route('admin.notifications') }}" class="admin-panel-link">All notifications <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </header>
            @if($recentNotifications->isEmpty())
                <p class="admin-empty-state">You have no recent notifications.</p>
            @else
                <ul class="admin-data-list">
                    @foreach($recentNotifications as $notification)
                        <li>
                            <span>{{ Str::limit(strip_tags($notification->message ?? ''), 180) }}</span>
                            @if($notification->created_at)
                                <time datetime="{{ $notification->created_at->toAtomString() }}">{{ $notification->created_at->diffForHumans() }}</time>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
