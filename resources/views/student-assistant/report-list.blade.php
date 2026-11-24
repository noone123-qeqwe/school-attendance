<ul class="list-unstyled d-grid gap-2 mb-0">
    @foreach($attendanceReports as $report)
        <li class="border rounded-3 px-3 py-2">
            <div class="d-flex flex-wrap align-items-baseline gap-2 justify-content-between">
                <strong class="text-break">{{ $report->student?->name ?? 'Classmate' }}</strong>
                <span class="status-pill status-{{ $report->review_status === 'approved' ? 'completed' : 'upcoming' }}">
                    {{ match ($report->review_status) {'approved' => 'Approved', 'dismissed' => 'Dismissed', default => 'Awaiting review'} }}
                </span>
            </div>
            <div class="text-break" style="color:#b39b82;">
                @if($showReportSession)
                    {{ $report->session?->subject?->name ?? 'Class' }} ·
                @endif
                {{ ucfirst($report->suggested_status) }} · {{ $report->created_at?->format($showReportSession ? 'M j, g:i A' : 'g:i A') }}
            </div>
            @if($report->reason)
                <p class="mb-0 mt-1 text-break">{{ $report->reason }}</p>
            @endif
        </li>
    @endforeach
</ul>
