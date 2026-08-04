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
                    <div class="table-responsive">
                        <table class="table table-borderless" style="color: #f3e7cd; margin-bottom: 0;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); color: #b39b82; font-size: 0.8rem; text-transform: uppercase;">
                                    <th style="padding: 12px 16px;">Time</th>
                                    <th style="padding: 12px 16px;">Subject</th>
                                    <th style="padding: 12px 16px;">Room</th>
                                    <th style="padding: 12px 16px;">Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($weeklySchedule[$day] as $sched)
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                                    <td style="padding: 16px;">
                                        <div style="font-weight: 700; color: var(--gold);">
                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td style="padding: 16px;">
                                        <div style="font-weight: 700;">{{ $sched->subject->name }}</div>
                                        <div style="font-size: 0.8rem; color: #b39b82;">{{ $sched->subject->code }}</div>
                                    </td>
                                    <td style="padding: 16px;">
                                        <x-badge type="info">{{ $sched->room ?? 'TBA' }}</x-badge>
                                    </td>
                                    <td style="padding: 16px;">
                                        <div style="font-size: 0.9rem;">{{ $sched->teacher->name ?? 'TBA' }}</div>
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
