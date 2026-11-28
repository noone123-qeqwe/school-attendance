@extends('layouts.app')
@section('page-title', 'Dashboard')
@section('content')
@php
    $studentUser = Auth::user();
    $campusNow = now();
    $greetHour = $campusNow->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
    $greetIcon = $greetHour < 12 ? 'bi-brightness-alt-high-fill' : ($greetHour < 17 ? 'bi-sun-fill' : 'bi-moon-stars-fill');
    $academicDetails = array_filter([
        $studentUser->course,
        filled($studentUser->year_level) ? 'Year ' . $studentUser->year_level : null,
        filled($studentUser->semester) ? 'Sem ' . $studentUser->semester : null,
        filled($studentUser->section) ? 'Sec ' . $studentUser->section : null,
    ], 'filled');
    $todayClasses = collect($todaySchedule);
    $featuredClass = $currentSlot ?? $upcomingClass;
    $featuredRoom = trim((string) ($featuredClass?->schedule?->room ?? ''));
    $featuredTeacher = $featuredClass?->subject?->instructorUser?->name ?? $featuredClass?->subject?->instructor;
    $todayRecordsUrl = route('student.calendar', ['record_date' => $campusNow->toDateString()]) . '#attendance-records';
    $peerAvailable = app(\App\Services\PeerBiometricAttendanceService::class)->isAvailable();
@endphp
<div class="sd-cockpit">
    <header class="sd-masthead" aria-labelledby="sdStudentName">
        <div class="sd-identity-cluster">
            <div class="sd-avatar-ring" aria-hidden="true"><img src="{{ $studentUser->profile_photo_url_with_version }}" alt=""></div>
            <div class="sd-identity-copy">
                <div class="sd-kicker-greeting"><i class="bi {{ $greetIcon }}" aria-hidden="true"></i> {{ $greeting }}</div>
                <h1 id="sdStudentName" class="sd-student-name">{{ $studentUser->name }}</h1>
            </div>
            @if(filled($studentUser->student_number) || $academicDetails)
                <ul class="sd-meta-ribbon" aria-label="Student details">
                    @if(filled($studentUser->student_number))<li class="sd-student-id sd-mono">ID {{ $studentUser->student_number }}</li>@endif
                    @foreach($academicDetails as $detail)<li>{{ $detail }}</li>@endforeach
                </ul>
            @endif
        </div>
        <div class="sd-campus-time" title="Campus local time">
            <span id="studentDate">{{ $campusNow->format('D, M j') }}</span><span id="studentClock">{{ $campusNow->format('g:i A') }}</span>
        </div>
    </header>
    @include('partials.missing-section-notice')
    @foreach(['error', 'success'] as $flashType)
        @if(session($flashType))
            <div class="sd-{{ $flashType }}-banner" role="{{ $flashType === 'error' ? 'alert' : 'status' }}">{{ session($flashType) }}</div>
        @endif
    @endforeach

    <div class="sd-home-layout">
        <div class="sd-home-main">
            <section class="sd-home-focus" id="sdHeroSpotlight" aria-labelledby="sdFocusHeading"
                     @if($featuredClass) data-start-ms="{{ $featuredClass->start_time->getTimestampMs() }}" data-end-ms="{{ $featuredClass->end_time->getTimestampMs() }}" @endif>
                <div class="sd-home-focus-label" aria-live="polite">
                    <span id="sdClassTiming">{{ $currentClass ? 'Happening now' : ($featuredClass ? 'Your next class' : 'Your schedule') }}</span>
                    @if($featuredClass)<span class="sd-home-code">{{ $featuredClass->subject->code }}</span>@endif
                </div>
                <h2 id="sdFocusHeading">{{ $featuredClass?->subject?->name ?? ($todayClasses->isEmpty() ? 'Free schedule today' : 'Today’s scheduled classes have ended') }}</h2>
                @if($featuredClass)
                    <p class="sd-home-class-time">
                        @unless($featuredClass->start_time->isToday()){{ $featuredClass->start_time->format('D, M j') }} &middot; @endunless
                        {{ $featuredClass->start_time->format('g:i A') }} &ndash; {{ $featuredClass->end_time->format('g:i A') }}
                    </p>
                    <div class="sd-home-class-meta">
                        <span><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $featuredRoom && strtoupper($featuredRoom) !== 'TBA' ? $featuredRoom : 'Room not assigned' }}</span>
                        @if(filled($featuredTeacher))<span><i class="bi bi-person" aria-hidden="true"></i> {{ $featuredTeacher }}</span>@endif
                    </div>
                    @if($currentClass && $alreadyClockedIn)
                        <p class="sd-home-confirmation"><i class="bi bi-check-circle" aria-hidden="true"></i> Attendance recorded for {{ $currentClass->code }} today.</p>
                    @endif
                @elseif($subjects->isEmpty())
                    <p>Your classes will appear after enrollment. Contact the registrar if your subjects are missing.</p>
                @else
                    <p>No upcoming class found in the next seven days. Check your timetable for your regular schedule.</p>
                @endif
                <div class="sd-home-checkin">
                    <div><h3>Record attendance</h3><p>Use the QR code or PIN shared by your instructor.</p></div>
                    <div class="sd-home-buttons">
                        <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-cta-primary"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Scan QR</a>
                        <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sd-cta-secondary"><i class="bi bi-123" aria-hidden="true"></i> Enter PIN</a>
                    </div>
                    @if($peerAvailable)<a href="{{ route('peer-snap.index') }}" class="sd-home-link">Check in for a classmate <i class="bi bi-arrow-right" aria-hidden="true"></i></a>@endif
                </div>
            </section>
            <section class="sd-home-panel" aria-labelledby="sdTodayHeading">
                <header class="sd-home-panel-head"><h2 id="sdTodayHeading">Today's classes</h2><a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-home-link">Full timetable <i class="bi bi-arrow-right" aria-hidden="true"></i></a></header>
                @if($todayClasses->isNotEmpty())
                    <ol class="sd-home-agenda">
                        @foreach($todayClasses as $item)
                            @php
                                $record = $item->attendance;
                                $recordStatus = $record?->excused ? 'Excused' : $record?->status;
                                $scheduleLabel = match($item->schedule_status) {
                                    'ongoing' => 'In progress', 'upcoming' => 'Upcoming', default => 'Ended',
                                };
                                $room = trim((string) $item->schedule->room);
                            @endphp
                            <li class="{{ $item->schedule_status === 'ongoing' ? 'is-current' : '' }}">
                                <div class="sd-home-agenda-time"><time datetime="{{ $item->start_time->toIso8601String() }}">{{ $item->start_time->format('g:i A') }}</time><span>{{ $item->end_time->format('g:i A') }}</span></div>
                                <div class="sd-home-agenda-class"><h3>{{ $item->subject->name }}</h3><p>{{ $item->subject->code }} @if($room && strtoupper($room) !== 'TBA') &middot; {{ $room }} @endif</p><span class="sd-home-schedule-label">{{ $scheduleLabel }}</span></div>
                                <div class="sd-home-agenda-status">
                                    @if($record)
                                        <a href="{{ route('student.calendar', ['q' => $item->subject->code, 'record_date' => $campusNow->toDateString()]) }}#attendance-records" class="sd-home-status is-{{ strtolower($recordStatus) }}">{{ $recordStatus }}{{ $item->has_multiple_slots ? ' · day record' : '' }}</a>
                                    @elseif($item->schedule_status === 'ended')
                                        <a href="{{ $todayRecordsUrl }}" class="sd-home-link">No record <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                                    @else
                                        <span class="sd-home-muted">Not recorded</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    <footer class="sd-home-panel-foot"><a href="{{ $todayRecordsUrl }}" class="sd-home-link">Review today's attendance <i class="bi bi-arrow-right" aria-hidden="true"></i></a></footer>
                @else
                    <p class="sd-home-empty">{{ $todayHoliday ? $todayHoliday->name . ' — no regular classes today.' : 'No classes scheduled for ' . $campusNow->format('l') . '.' }}</p>
                @endif
            </section>
            <section class="sd-home-panel" aria-labelledby="sdRecentHeading">
                <header class="sd-home-panel-head"><h2 id="sdRecentHeading">Recent attendance</h2><a href="{{ route('student.calendar') }}#attendance-records" class="sd-home-link">All records <i class="bi bi-arrow-right" aria-hidden="true"></i></a></header>
                @if($recentAttendance->isNotEmpty())
                    <ul class="sd-home-records">
                        @foreach($recentAttendance as $record)
                            @php($status = $record->excused ? 'Excused' : $record->status)
                            <li><a href="{{ route('student.calendar', ['q' => $record->subject_code, 'record_date' => $record->date->toDateString()]) }}#attendance-records">
                                <div><strong>{{ $record->subject?->name ?? $record->subject_name ?? $record->subject_code }}</strong><span>{{ $record->subject_code }} &middot; {{ $record->date->format('M j') }}@if($record->time_in && in_array($status, ['Present', 'Late'], true)) &middot; {{ \Carbon\Carbon::parse($record->time_in)->format('g:i A') }}@endif</span></div>
                                <span class="sd-home-status is-{{ strtolower($status) }}">{{ $status }}</span>
                            </a></li>
                        @endforeach
                    </ul>
                @else
                    <p class="sd-home-empty">Your first attendance record will appear here after you check in.</p>
                @endif
            </section>
        </div>
        <aside class="sd-home-aside" aria-label="Student updates">
            <section class="sd-home-panel" aria-labelledby="sdAttentionHeading">
                <header class="sd-home-panel-head"><h2 id="sdAttentionHeading">Needs your attention</h2></header>
                @if($activeWarnings->isNotEmpty())
                    <div class="sd-home-warnings" role="region" aria-label="Attendance Warnings" @include('partials.student-warning-alert-attributes')>
                        @foreach($activeWarnings as $warning)<article><span class="sd-home-code">{{ $warning->subject_code }}</span><p>{{ $warning->message }}</p></article>@endforeach
                        <a href="{{ route('student.calendar') }}#attendance-records" class="sd-home-link">Review attendance <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                @else
                    <span hidden data-alert-resolved="student-attendance-warnings"></span>
                @endif
                @if($unresolvedAbsences->isNotEmpty())
                    <p class="sd-home-section-note">{{ $unresolvedAbsencesCount }} {{ \Illuminate\Support\Str::plural('absence', $unresolvedAbsencesCount) }} without a pending or approved excuse.</p>
                    <ul class="sd-home-action-list">
                        @foreach($unresolvedAbsences as $absence)
                            <li><strong>{{ $absence->subject?->name ?? $absence->subject_name ?? $absence->subject_code }}</strong><span>{{ $absence->date->format('D, M j') }} &middot; Absent</span><a href="{{ route('excuses.create', $absence->id) }}" class="sd-home-link">Submit an excuse <i class="bi bi-arrow-right" aria-hidden="true"></i></a></li>
                        @endforeach
                    </ul>
                    @if($unresolvedAbsencesCount > $unresolvedAbsences->count())<footer class="sd-home-panel-foot"><a href="{{ route('excuses') }}" class="sd-home-link">Review all absences</a></footer>@endif
                @elseif($activeWarnings->isEmpty())
                    <p class="sd-home-empty"><i class="bi bi-check-circle" aria-hidden="true"></i> No recorded absences or warnings to review.</p>
                @endif
            </section>
            <section class="sd-home-panel" aria-labelledby="sdExcusesHeading">
                <header class="sd-home-panel-head"><h2 id="sdExcusesHeading">Excuse requests</h2><a href="{{ route('excuses') }}" class="sd-home-link">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a></header>
                @if($recentExcuseRequests->isNotEmpty())
                    <ul class="sd-home-requests">
                        @foreach($recentExcuseRequests as $request)
                            <li>
                                <div class="sd-home-request-title"><strong>{{ $request->attendance->subject_code }}</strong><span class="sd-home-status is-{{ $request->status }}">{{ ucfirst($request->status) }}</span></div>
                                <p>{{ $request->reason }} &middot; {{ $request->attendance->date->format('M j') }}</p>
                                @if($request->status === 'pending')<span class="sd-home-muted">Awaiting review</span>@endif
                                @if(filled($request->admin_notes))<p class="sd-home-review-note">{{ $request->admin_notes }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="sd-home-empty">No excuse requests yet.</p>
                @endif
                <footer class="sd-home-panel-foot"><a href="{{ route('excuses.create_general') }}" class="sd-home-link">Submit an excuse <i class="bi bi-plus-lg" aria-hidden="true"></i></a></footer>
            </section>
            @if($announcements->isNotEmpty())
                <section class="sd-home-panel" id="studentAnnouncementsSection" aria-labelledby="studentAnnouncementsHeading" data-alert-group>
                    <header class="sd-home-panel-head"><h2 id="studentAnnouncementsHeading">School announcements</h2></header>
                    @foreach($announcements as $announcement)
                        <article class="sd-home-announcement" data-alert-banner data-alert-key="student-announcement-{{ $announcement->id }}" data-alert-version="{{ $announcement->updated_at?->format('U.u') }}" data-alert-label="Dismiss announcement">
                            <h3>{{ $announcement->title }}</h3>
                            <p class="sd-home-announcement-meta">{{ $announcement->author->name ?? 'Administration' }} &middot; {{ ($announcement->scheduled_for ?? $announcement->created_at)->format('M j') }}</p>
                            @if(\Illuminate\Support\Str::length($announcement->content) > 200)
                                <p>{{ \Illuminate\Support\Str::limit($announcement->content, 150) }}</p>
                                <details><summary>Read full announcement</summary><p>{{ $announcement->content }}</p></details>
                            @else
                                <p>{{ $announcement->content }}</p>
                            @endif
                        </article>
                    @endforeach
                </section>
            @endif
        </aside>
    </div>
</div>
<script nonce="{{ csp_nonce() }}">
(() => {
    const timeZone = @json(config('app.timezone'));
    const clockFormat = new Intl.DateTimeFormat('en-US', { timeZone, hour: 'numeric', minute: '2-digit' });
    const dateFormat = new Intl.DateTimeFormat('en-US', { timeZone, weekday: 'short', month: 'short', day: 'numeric' });
    const tick = () => {
        if (document.hidden) return;
        const now = new Date();
        document.getElementById('studentClock').textContent = clockFormat.format(now);
        document.getElementById('studentDate').textContent = dateFormat.format(now);
        const spotlight = document.getElementById('sdHeroSpotlight');
        const starts = Number(spotlight.dataset.startMs);
        const ends = Number(spotlight.dataset.endMs);
        if (starts && ends) {
            const timing = document.getElementById('sdClassTiming');
            if (now.getTime() > ends) timing.textContent = 'Class has ended';
            else if (now.getTime() >= starts) timing.textContent = 'Happening now';
        }
    };
    tick();
    document.addEventListener('visibilitychange', tick);
    const scheduleTick = () => {
        setTimeout(() => { tick(); scheduleTick(); }, 60000 - (Date.now() % 60000) + 120);
    };
    scheduleTick();
})();
</script>
@endsection
