@extends('layouts.app')
@section('page-title', 'My Schedule')

@section('content')
<div class="mb-4">
    <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: 2rem;">Weekly Schedule</h1>
    <div style="color: #b39b82; font-size: 0.95rem;">
        Your classes for Year {{ Auth::user()->year_level }}, Semester {{ Auth::user()->semester }}
    </div>
</div>

<div class="row g-4">
    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
        <div class="col-lg-12">
            <x-card title="{{ $day }}" icon="bi bi-calendar-event">
                @if($weeklySchedule[$day]->count() > 0)
                    <div class="ent-scroll-x" style="margin: -20px;">
                        <table class="ent-table" style="min-width: 600px; margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Subject</th>
                                    <th>Room</th>
                                    <th>Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($weeklySchedule[$day] as $sched)
                                <tr>
                                    <td data-label="Time">
                                        <div style="font-weight: 700; color: var(--ent-gold);">
                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td data-label="Subject">
                                        <div style="font-weight: 700; color: var(--ent-text);">{{ $sched->subject->name }}</div>
                                        <div class="ent-text-muted" style="font-size: 0.8rem;">{{ $sched->subject->code }}</div>
                                    </td>
                                    <td data-label="Room">
                                        <span class="ent-badge ent-badge-info">{{ $sched->room ?? 'TBA' }}</span>
                                    </td>
                                    <td data-label="Instructor">
                                        <div style="font-size: 0.9rem;">{{ $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-cup-hot" style="font-size: 2rem; color: rgba(255,255,255,0.1);"></i>
                        <div style="color: #b39b82; font-size: 0.9rem; margin-top: 8px;">No classes scheduled for this day.</div>
                    </div>
                @endif
            </x-card>
        </div>
    @endforeach
</div>
@endsection
