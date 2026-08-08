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
                    <!-- Desktop Table -->
                    <div class="ent-scroll-x d-none d-md-block" style="margin: -20px;">
                        <table class="ent-table" style="min-width: 600px; margin-bottom: 0; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 25%;">
                                <col style="width: 40%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                            </colgroup>
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
                                        <div style="font-weight: 700; color: var(--gold);">
                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td data-label="Subject">
                                        <div style="font-weight: 700; color: #f3e7cd;">{{ $sched->subject->name }}</div>
                                        <div class="ent-text-muted" style="font-size: 0.8rem;">{{ $sched->subject->code }}</div>
                                    </td>
                                    <td data-label="Room">
                                        <span style="background: rgba(14,165,233,0.15); color: #38bdf8; border: 1px solid rgba(14,165,233,0.3); padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">{{ $sched->room ?? 'TBA' }}</span>
                                    </td>
                                    <td data-label="Instructor">
                                        <div style="font-size: 0.9rem;">{{ $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="d-block d-md-none">
                        <div class="d-flex flex-column gap-3 pt-3">
                            @foreach($weeklySchedule[$day] as $sched)
                                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
                                    <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--gold);"></div>
                                    <div class="d-flex justify-content-between align-items-start mb-3" style="padding-left: 8px;">
                                        <div>
                                            <div style="font-weight: 800; color: #f3e7cd; font-size: 1.1rem; line-height: 1.2;">{{ $sched->subject->name }}</div>
                                            <div style="font-size: 0.8rem; color: var(--gold); font-weight: 700; margin-top: 4px; letter-spacing: 0.5px;">{{ $sched->subject->code }}</div>
                                        </div>
                                        <span style="background: rgba(14,165,233,0.15); color: #38bdf8; border: 1px solid rgba(14,165,233,0.3); padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">{{ $sched->room ?? 'TBA' }}</span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-3" style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 12px; margin-bottom: 12px; margin-left: 8px; border: 1px solid rgba(255,255,255,0.03);">
                                        <div style="width: 32px; height: 32px; background: rgba(207,164,111,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-clock-fill" style="color: var(--gold); font-size: 1rem;"></i>
                                        </div>
                                        <div style="color: #d6b67b; font-weight: 700; font-size: 0.95rem;">
                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-3" style="padding: 0 4px 0 12px;">
                                        <div style="width: 28px; height: 28px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.1);">
                                            <i class="bi bi-person-fill" style="color: #b39b82; font-size: 0.9rem;"></i>
                                        </div>
                                        <div style="color: #e5e5e5; font-size: 0.9rem; font-weight: 600;">
                                            {{ $sched->subject->instructorUser->name ?? $sched->subject->instructor ?? 'TBA' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-cup-hot" style="font-size: 2.5rem; color: rgba(255,255,255,0.08); margin-bottom: 16px; display: block;"></i>
                        <div style="color: #b39b82; font-size: 0.9rem; margin-top: 8px;">No classes scheduled for this day.</div>
                    </div>
                @endif
            </x-card>
        </div>
    @endforeach
</div>
@endsection
